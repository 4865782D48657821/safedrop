<?php

namespace Tests\Unit;

use App\Enums\DomainStatus;
use App\Models\ExternalTarget;
use App\Models\Release;
use App\Services\UrlReviewService;
use InvalidArgumentException;
use Tests\TestCase;

class UrlReviewServiceTest extends TestCase
{
    public function test_public_https_project_page_is_normalized_and_marked_pending_review(): void
    {
        $review = $this->service()->review('HTTPS://Modrinth.Com/plugin/example#reviews');

        $this->assertSame('https://modrinth.com/plugin/example', $review->normalizedUrl);
        $this->assertSame(['https://modrinth.com/plugin/example'], $review->redirectChain);
        $this->assertSame('modrinth.com', $review->targetDomain);
        $this->assertSame('project_page', $review->targetType);
        $this->assertSame('reachable', $review->reachabilityStatus);
        $this->assertSame('pending', $review->trustStatus);
        $this->assertSame([], $review->signals);
    }

    public function test_http_and_known_shorteners_require_manual_review(): void
    {
        $httpReview = $this->service()->review('http://example.com/project');
        $shortenerReview = $this->service(['bit.ly' => ['93.184.216.34']])->review('https://bit.ly/project');

        $this->assertSame('needs_review', $httpReview->trustStatus);
        $this->assertSame(['non_https'], $httpReview->signals);

        $this->assertSame('needs_review', $shortenerReview->trustStatus);
        $this->assertSame(['known_shortener'], $shortenerReview->signals);
    }

    public function test_unsafe_urls_are_blocked(): void
    {
        $service = $this->service();

        $this->assertSame('blocked', $service->review('javascript:alert(1)')->trustStatus);
        $this->assertSame('blocked', $service->review('https://user:pass@example.com/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://localhost/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://127.0.0.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://127.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://127.0.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://10.0.0.4/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://10.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://192.168.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://169.254.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://[::1]/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://[fe80::1]/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://2130706433/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://0x7f000001/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://0177.0.0.1/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://example.local/project')->trustStatus);
    }

    public function test_unresolvable_dns_marks_target_unreachable_for_manual_review(): void
    {
        $review = $this->service(['missing.example' => []])->review('https://missing.example/project');

        $this->assertSame('https://missing.example/project', $review->normalizedUrl);
        $this->assertSame('missing.example', $review->targetDomain);
        $this->assertSame('unreachable', $review->reachabilityStatus);
        $this->assertSame('needs_review', $review->trustStatus);
        $this->assertSame(['dns_unresolved'], $review->signals);
    }

    public function test_dns_resolution_to_private_or_reserved_addresses_is_blocked(): void
    {
        $service = $this->service([
            'unspecified.example' => ['0.0.0.0'],
            'private.example' => ['10.0.0.5'],
            'cgnat.example' => ['100.64.0.1'],
            'loopback.example' => ['127.0.0.1'],
            'documentation.example' => ['192.0.2.1'],
            'benchmark.example' => ['198.18.0.1'],
            'multicast.example' => ['224.0.0.1'],
            'reserved.example' => ['240.0.0.1'],
            'mixed.example' => ['93.184.216.34', '192.168.1.10'],
            'ipv6-unspecified.example' => ['::'],
            'ipv6-loopback.example' => ['::1'],
            'ipv6-mapped.example' => ['::ffff:127.0.0.1'],
            'ipv6-documentation.example' => ['2001:db8::1'],
            'ipv6-private.example' => ['fd00::1'],
            'ipv6-link-local.example' => ['fe80::1'],
            'ipv6-multicast.example' => ['ff00::1'],
        ]);

        $this->assertSame('blocked', $service->review('https://unspecified.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://private.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://cgnat.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://loopback.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://documentation.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://benchmark.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://multicast.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://reserved.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://mixed.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-unspecified.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-loopback.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-mapped.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-documentation.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-private.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-link-local.example/project')->trustStatus);
        $this->assertSame('blocked', $service->review('https://ipv6-multicast.example/project')->trustStatus);
        $this->assertSame(['dns_resolves_to_private_or_reserved_address'], $service->review('https://private.example/project')->signals);
    }

    public function test_public_ip_literal_is_reachable_without_dns_lookup(): void
    {
        $review = $this->service()->review('https://93.184.216.34/project');
        $ipv6Review = $this->service()->review('https://[2606:4700:4700::1111]/project');

        $this->assertSame('reachable', $review->reachabilityStatus);
        $this->assertSame('pending', $review->trustStatus);
        $this->assertSame('reachable', $ipv6Review->reachabilityStatus);
        $this->assertSame('pending', $ipv6Review->trustStatus);
    }

    public function test_shortener_subdomains_require_manual_review(): void
    {
        $review = $this->service(['www.tinyurl.com' => ['93.184.216.34']])->review('https://www.tinyurl.com/project');

        $this->assertSame('needs_review', $review->trustStatus);
        $this->assertSame(['known_shortener'], $review->signals);
    }

    public function test_redirect_chain_is_resolved_and_final_url_becomes_review_target(): void
    {
        $review = $this->service(
            [
                'downloads.example' => ['93.184.216.34'],
                'cdn.example' => ['93.184.216.35'],
            ],
            [
                'https://downloads.example/project' => 'https://cdn.example/projects/current',
            ],
        )->review('https://downloads.example/project');

        $this->assertSame('https://cdn.example/projects/current', $review->normalizedUrl);
        $this->assertSame([
            'https://downloads.example/project',
            'https://cdn.example/projects/current',
        ], $review->redirectChain);
        $this->assertSame('cdn.example', $review->targetDomain);
        $this->assertSame('needs_review', $review->trustStatus);
        $this->assertSame(['redirect_domain_changed'], $review->signals);
    }

    public function test_relative_redirect_locations_are_resolved_against_current_url(): void
    {
        $review = $this->service(
            ['example.com' => ['93.184.216.34']],
            ['https://example.com/releases/latest' => '/downloads/current?from=latest'],
        )->review('https://example.com/releases/latest');

        $this->assertSame('https://example.com/downloads/current?from=latest', $review->normalizedUrl);
        $this->assertSame([
            'https://example.com/releases/latest',
            'https://example.com/downloads/current?from=latest',
        ], $review->redirectChain);
        $this->assertSame('example.com', $review->targetDomain);
        $this->assertSame('pending', $review->trustStatus);
        $this->assertSame([], $review->signals);
    }

    public function test_redirects_to_private_or_reserved_destinations_are_blocked(): void
    {
        $review = $this->service(
            ['example.com' => ['93.184.216.34']],
            ['https://example.com/project' => 'https://127.0.0.1/admin'],
        )->review('https://example.com/project');

        $this->assertSame('blocked', $review->trustStatus);
        $this->assertSame('127.0.0.1', $review->targetDomain);
        $this->assertSame(['redirect_to_blocked_destination', 'blocked_host'], $review->signals);
    }

    public function test_redirect_chains_over_limit_require_manual_review(): void
    {
        config(['safedrop.url_review.max_redirects' => 1]);

        $review = $this->service(
            ['example.com' => ['93.184.216.34']],
            [
                'https://example.com/a' => 'https://example.com/b',
                'https://example.com/b' => 'https://example.com/c',
            ],
        )->review('https://example.com/a');

        $this->assertSame('https://example.com/b', $review->normalizedUrl);
        $this->assertSame('needs_review', $review->trustStatus);
        $this->assertSame(['redirect_chain_too_long'], $review->signals);
    }

    public function test_default_redirect_resolution_rechecks_dns_before_connecting(): void
    {
        config(['safedrop.url_review.resolve_redirects' => true]);

        $calls = 0;
        $service = new UrlReviewService(function () use (&$calls): array {
            $calls++;

            return $calls === 1 ? ['93.184.216.34'] : ['127.0.0.1'];
        });

        $review = $service->review('https://example.com/project');

        $this->assertSame('https://example.com/project', $review->normalizedUrl);
        $this->assertSame('pending', $review->trustStatus);
        $this->assertSame(2, $calls);
    }

    public function test_non_mvp_target_types_are_blocked(): void
    {
        $review = $this->service()->review('https://example.com/file.zip', 'file_download');

        $this->assertSame('blocked', $review->trustStatus);
        $this->assertSame(['target_type_not_in_mvp'], $review->signals);
    }

    public function test_external_target_can_be_built_from_review_result(): void
    {
        $release = (new Release)->forceFill(['id' => 123]);
        $review = $this->service(['create.roblox.com' => ['93.184.216.34']])->review('https://create.roblox.com/store/asset/example');

        $target = ExternalTarget::makeFromReview($release, $review);

        $this->assertSame(123, $target->release_id);
        $this->assertSame('https://create.roblox.com/store/asset/example', $target->normalized_url);
        $this->assertSame('create.roblox.com', $target->target_domain);
        $this->assertSame(DomainStatus::New, $target->domain_status);
        $this->assertSame('pending', $target->trust_status);
    }

    public function test_review_signals_map_to_suspicious_domain_status(): void
    {
        $release = (new Release)->forceFill(['id' => 123]);
        $review = $this->service(['bit.ly' => ['93.184.216.34']])->review('https://bit.ly/project');

        $target = ExternalTarget::makeFromReview($release, $review);

        $this->assertSame(DomainStatus::Suspicious, $target->domain_status);
    }

    public function test_blocked_review_results_cannot_be_built_as_external_targets(): void
    {
        $release = (new Release)->forceFill(['id' => 123]);
        $review = $this->service()->review('javascript:alert(1)');

        $this->expectException(InvalidArgumentException::class);

        ExternalTarget::makeFromReview($release, $review);
    }

    private function service(
        array $dns = ['example.com' => ['93.184.216.34'], 'modrinth.com' => ['151.101.2.132']],
        array $redirects = [],
    ): UrlReviewService {
        return new UrlReviewService(
            fn (string $host): array => $dns[$host] ?? [],
            fn (string $url): ?string => $redirects[$url] ?? null,
        );
    }
}
