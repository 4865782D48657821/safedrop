<?php

namespace App\Services;

use App\Support\UrlReviewResult;
use Closure;
use Throwable;

class UrlReviewService
{
    private const BLOCKED_IPV4_CIDRS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
    ];

    private const BLOCKED_IPV6_CIDRS = [
        '::/128',
        '::1/128',
        '::ffff:0:0/96',
        '64:ff9b::/96',
        '100::/64',
        '2001::/23',
        '2001:db8::/32',
        '2002::/16',
        'fc00::/7',
        'fe80::/10',
        'ff00::/8',
    ];

    public function __construct(
        private ?Closure $dnsResolver = null,
        private ?Closure $redirectResolver = null,
    ) {}

    public function review(string $url, string $targetType = 'project_page'): UrlReviewResult
    {
        $originalUrl = trim($url);
        $signals = [];

        if (! in_array($targetType, config('safedrop.mvp_external_target_types'), true)) {
            return $this->blocked($originalUrl, $targetType, ['target_type_not_in_mvp']);
        }

        $current = $this->inspectUrl($originalUrl);

        if ($current['blocked']) {
            return $this->blocked($originalUrl, $targetType, $current['signals'], $current['host']);
        }

        $signals = array_merge($signals, $current['signals']);
        $redirectChain = [$current['normalizedUrl']];
        $initialHost = $current['host'];
        $maxRedirects = max(0, (int) config('safedrop.url_review.max_redirects', 5));
        $redirectChainTooLong = false;
        $redirectChainComplete = false;

        for ($hop = 0; $hop < $maxRedirects; $hop++) {
            $redirectUrl = $this->nextRedirectUrl($current['normalizedUrl']);

            if ($redirectUrl === null) {
                $redirectChainComplete = true;
                break;
            }

            $next = $this->inspectUrl($this->resolveRedirectLocation($current['normalizedUrl'], $redirectUrl));

            if ($next['blocked']) {
                return $this->blocked(
                    $originalUrl,
                    $targetType,
                    array_values(array_unique(array_merge($signals, ['redirect_to_blocked_destination'], $next['signals']))),
                    $next['host'],
                );
            }

            $signals = array_merge($signals, $next['signals']);
            $redirectChain[] = $next['normalizedUrl'];
            $current = $next;
        }

        if (! $redirectChainComplete && $this->nextRedirectUrl($current['normalizedUrl']) !== null) {
            $redirectChainTooLong = true;
        }

        if ($redirectChainTooLong) {
            $signals[] = 'redirect_chain_too_long';
        }

        if ($current['host'] !== $initialHost) {
            $signals[] = 'redirect_domain_changed';
        }

        $signals = array_values(array_unique($signals));

        return new UrlReviewResult(
            originalUrl: $originalUrl,
            normalizedUrl: $current['normalizedUrl'],
            redirectChain: $redirectChain,
            targetDomain: $current['host'],
            targetType: $targetType,
            reachabilityStatus: $current['reachable'] ? 'reachable' : 'unreachable',
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
            reachabilityStatus: 'unreachable',
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

    /**
     * @return array{blocked: bool, normalizedUrl: ?string, host: ?string, reachable: bool, signals: list<string>}
     */
    private function inspectUrl(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return $this->inspectionBlocked(['malformed_url']);
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(rtrim($parts['host'], '.'));
        $hostForIpChecks = $this->hostForIpChecks($host);

        if (! in_array($scheme, config('safedrop.url_review.allowed_schemes'), true)) {
            return $this->inspectionBlocked(['unsupported_scheme'], $host);
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return $this->inspectionBlocked(['userinfo_not_allowed'], $host);
        }

        if ($this->isBlockedHost($hostForIpChecks)) {
            return $this->inspectionBlocked(['blocked_host'], $host);
        }

        $signals = [];

        if ($scheme !== 'https') {
            $signals[] = 'non_https';
        }

        if ($this->isKnownShortener($host)) {
            $signals[] = 'known_shortener';
        }

        $dnsResult = $this->resolvePublicHost($hostForIpChecks);

        if ($dnsResult['blocked']) {
            return $this->inspectionBlocked(['dns_resolves_to_private_or_reserved_address'], $host);
        }

        if (! $dnsResult['reachable']) {
            $signals[] = 'dns_unresolved';
        }

        return [
            'blocked' => false,
            'normalizedUrl' => $this->normalize($parts, $scheme, $host),
            'host' => $host,
            'reachable' => $dnsResult['reachable'],
            'signals' => $signals,
        ];
    }

    /**
     * @return array{blocked: true, normalizedUrl: null, host: ?string, reachable: false, signals: list<string>}
     */
    private function inspectionBlocked(array $signals, ?string $host = null): array
    {
        return [
            'blocked' => true,
            'normalizedUrl' => null,
            'host' => $host,
            'reachable' => false,
            'signals' => $signals,
        ];
    }

    private function nextRedirectUrl(string $url): ?string
    {
        try {
            $redirectUrl = $this->redirectResolver instanceof Closure
                ? ($this->redirectResolver)($url)
                : ($this->shouldResolveRedirects() ? $this->defaultRedirectResolve($url) : null);
        } catch (Throwable) {
            return null;
        }

        return is_string($redirectUrl) && trim($redirectUrl) !== '' ? trim($redirectUrl) : null;
    }

    private function shouldResolveRedirects(): bool
    {
        return (bool) config('safedrop.url_review.resolve_redirects', true);
    }

    private function resolveRedirectLocation(string $currentUrl, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $currentParts = parse_url($currentUrl);

        if ($currentParts === false || empty($currentParts['scheme']) || empty($currentParts['host'])) {
            return $location;
        }

        $scheme = strtolower($currentParts['scheme']);
        $host = strtolower(rtrim($currentParts['host'], '.'));
        $port = isset($currentParts['port']) ? ":{$currentParts['port']}" : '';

        if (str_starts_with($location, '//')) {
            return "{$scheme}:{$location}";
        }

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $basePath = $currentParts['path'] ?? '/';
        $baseDir = str_ends_with($basePath, '/') ? $basePath : dirname($basePath).'/';

        return "{$scheme}://{$host}{$port}{$baseDir}{$location}";
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

    /**
     * @return array{reachable: bool, blocked: bool, addresses: list<string>}
     */
    private function resolvePublicHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [
                'reachable' => true,
                'blocked' => $this->isBlockedIpAddress($host),
                'addresses' => [$host],
            ];
        }

        $addresses = $this->resolveDnsAddresses($host);

        if ($addresses === []) {
            return [
                'reachable' => false,
                'blocked' => false,
                'addresses' => [],
            ];
        }

        foreach ($addresses as $address) {
            if ($this->isBlockedIpAddress($address)) {
                return [
                    'reachable' => false,
                    'blocked' => true,
                    'addresses' => $addresses,
                ];
            }
        }

        return [
            'reachable' => true,
            'blocked' => false,
            'addresses' => $addresses,
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveDnsAddresses(string $host): array
    {
        try {
            $addresses = $this->dnsResolver instanceof Closure
                ? ($this->dnsResolver)($host)
                : $this->defaultDnsResolve($host);
        } catch (Throwable) {
            return [];
        }

        if (! is_array($addresses)) {
            return [];
        }

        return array_values(array_filter($addresses, fn ($address): bool => is_string($address) && filter_var($address, FILTER_VALIDATE_IP) !== false));
    }

    /**
     * @return list<string>
     */
    private function defaultDnsResolve(string $host): array
    {
        $records = dns_get_record($host, DNS_A + DNS_AAAA);

        if ($records === false) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $addresses[] = $record['ip'];
            }

            if (isset($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return $addresses;
    }

    private function defaultRedirectResolve(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(rtrim($parts['host'], '.'));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (! in_array($scheme, ['http', 'https'], true) || $port < 1 || $port > 65535) {
            return null;
        }

        $dnsResult = $this->resolvePublicHost($this->hostForIpChecks($host));

        if ($dnsResult['blocked'] || $dnsResult['addresses'] === []) {
            return null;
        }

        foreach ($dnsResult['addresses'] as $address) {
            if ($this->isBlockedIpAddress($address)) {
                return null;
            }
        }

        $address = $dnsResult['addresses'][0];
        $addressForSocket = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[{$address}]" : $address;
        $transport = $scheme === 'https' ? 'ssl' : 'tcp';
        $timeout = (float) config('safedrop.url_review.redirect_timeout_seconds', 3);
        $contextOptions = [];

        if ($scheme === 'https') {
            $contextOptions['ssl'] = [
                'peer_name' => $host,
                'SNI_enabled' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ];
        }

        $socket = @stream_socket_client(
            "{$transport}://{$addressForSocket}:{$port}",
            $errorCode,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($contextOptions),
        );

        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, (int) ceil($timeout));

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? "?{$parts['query']}" : '';
        $hostHeader = $this->hostHeader($host, $scheme, $port);
        fwrite($socket, "HEAD {$path}{$query} HTTP/1.1\r\n");
        fwrite($socket, "Host: {$hostHeader}\r\n");
        fwrite($socket, "User-Agent: SafedropUrlReview/1.0\r\n");
        fwrite($socket, "Accept: */*\r\n");
        fwrite($socket, "Connection: close\r\n\r\n");

        $headers = '';

        while (! feof($socket) && strlen($headers) < 16384) {
            $headers .= (string) fgets($socket, 4096);

            if (str_contains($headers, "\r\n\r\n")) {
                break;
            }
        }

        fclose($socket);

        $lines = preg_split('/\r\n|\n|\r/', $headers) ?: [];
        $statusLine = $lines[0] ?? '';

        if (preg_match('/^HTTP\/\d(?:\.\d)?\s+3\d\d\b/i', $statusLine) !== 1) {
            return null;
        }

        foreach (array_slice($lines, 1) as $line) {
            if (! is_string($line) || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);

            if (strtolower(trim($name)) !== 'location') {
                continue;
            }

            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function hostHeader(string $host, string $scheme, int $port): string
    {
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $formattedHost = str_contains($host, ':') && ! str_starts_with($host, '[') ? "[{$host}]" : $host;

        return $port === $defaultPort ? $formattedHost : "{$formattedHost}:{$port}";
    }

    private function isBlockedIpAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->matchesAnyCidr($address, self::BLOCKED_IPV4_CIDRS);
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->matchesAnyCidr($address, self::BLOCKED_IPV6_CIDRS);
        }

        return true;
    }

    /**
     * @param  list<string>  $cidrs
     */
    private function matchesAnyCidr(string $address, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if ($this->matchesCidr($address, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $address, string $cidr): bool
    {
        [$range, $prefixLength] = explode('/', $cidr, 2);
        $addressBytes = inet_pton($address);
        $rangeBytes = inet_pton($range);

        if ($addressBytes === false || $rangeBytes === false || strlen($addressBytes) !== strlen($rangeBytes)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($addressBytes, 0, $fullBytes) !== substr($rangeBytes, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($addressBytes[$fullBytes]) & $mask) === (ord($rangeBytes[$fullBytes]) & $mask);
    }
}
