<?php

namespace App\Services;

use App\Support\UrlReviewResult;

class UrlReviewService
{
    public function review(string $url, string $targetType = 'project_page'): UrlReviewResult
    {
        $originalUrl = trim($url);
        $signals = [];

        if (! in_array($targetType, config('safedrop.mvp_external_target_types'), true)) {
            return $this->blocked($originalUrl, $targetType, ['target_type_not_in_mvp']);
        }

        $parts = parse_url($originalUrl);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return $this->blocked($originalUrl, $targetType, ['malformed_url']);
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(rtrim($parts['host'], '.'));
        $hostForIpChecks = $this->hostForIpChecks($host);

        if (! in_array($scheme, config('safedrop.url_review.allowed_schemes'), true)) {
            return $this->blocked($originalUrl, $targetType, ['unsupported_scheme']);
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return $this->blocked($originalUrl, $targetType, ['userinfo_not_allowed'], $host);
        }

        if ($this->isBlockedHost($hostForIpChecks)) {
            return $this->blocked($originalUrl, $targetType, ['blocked_host'], $host);
        }

        if ($scheme !== 'https') {
            $signals[] = 'non_https';
        }

        if ($this->isKnownShortener($host)) {
            $signals[] = 'known_shortener';
        }

        $normalizedUrl = $this->normalize($parts, $scheme, $host);

        return new UrlReviewResult(
            originalUrl: $originalUrl,
            normalizedUrl: $normalizedUrl,
            redirectChain: [$normalizedUrl],
            targetDomain: $host,
            targetType: $targetType,
            reachabilityStatus: 'unchecked',
            trustStatus: $signals === [] ? 'pending' : 'needs_review',
            signals: $signals,
        );
    }

    private function blocked(string $originalUrl, string $targetType, array $signals, ?string $host = null): UrlReviewResult
    {
        return new UrlReviewResult(
            originalUrl: $originalUrl,
            normalizedUrl: null,
            redirectChain: [],
            targetDomain: $host,
            targetType: $targetType,
            reachabilityStatus: 'unchecked',
            trustStatus: 'blocked',
            signals: $signals,
        );
    }

    private function normalize(array $parts, string $scheme, string $host): string
    {
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? "?{$parts['query']}" : '';
        $port = isset($parts['port']) ? ":{$parts['port']}" : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }

    private function hostForIpChecks(string $host): string
    {
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return substr($host, 1, -1);
        }

        return $host;
    }

    private function isBlockedHost(string $host): bool
    {
        if (in_array($host, config('safedrop.url_review.blocked_hosts'), true)) {
            return true;
        }

        foreach (config('safedrop.url_review.blocked_suffixes') as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return $this->isNonCanonicalIpLiteral($host);
    }

    private function isNonCanonicalIpLiteral(string $host): bool
    {
        if (preg_match('/^\d+$/', $host) === 1) {
            return true;
        }

        if (preg_match('/^0x[0-9a-f]+$/i', $host) === 1) {
            return true;
        }

        if (preg_match('/^[0-9.]+$/', $host) === 1 && str_contains($host, '.')) {
            return true;
        }

        if (preg_match('/^[0-9a-fx.]+$/i', $host) !== 1 || ! str_contains($host, '.')) {
            return false;
        }

        $labels = explode('.', $host);

        foreach ($labels as $label) {
            if ($label === '') {
                return false;
            }

            if (str_starts_with(strtolower($label), '0x')) {
                return true;
            }

            if (strlen($label) > 1 && str_starts_with($label, '0')) {
                return true;
            }
        }

        return false;
    }

    private function isKnownShortener(string $host): bool
    {
        foreach (config('safedrop.url_review.shortener_domains') as $domain) {
            if ($host === $domain || str_ends_with($host, ".{$domain}")) {
                return true;
            }
        }

        return false;
    }
}
