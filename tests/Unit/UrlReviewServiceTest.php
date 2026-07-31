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
        $review = app(UrlReviewService::class)->review('HTTPS://Modrinth.Com/plugin/example#reviews');

        $this->assertSame('https://modrinth.com/plugin/example', $review->normalizedUrl);
        $this->assertSame(['https://modrinth.com/plugin/example'], $review->redirectChain);
        $this->assertSame('modrinth.com', $review->targetDomain);
        $this->assertSame('project_page', $review->targetType);
        $this->assertSame('unchecked', $review->reachabilityStatus);
        $this->assertSame('pending', $review->trustStatus);
        $this->assertSame([], $review->signals);
    }

    public function test_http_and_known_shorteners_require_manual_review(): void
    {
        $httpReview = app(UrlReviewService::class)->review('http://example.com/project');
        $shortenerReview = app(UrlReviewService::class)->review('https://bit.ly/project');

        $this->assertSame('needs_review', $httpReview->trustStatus);
        $this->assertSame(['non_https'], $httpReview->signals);

        $this->assertSame('needs_review', $shortenerReview->trustStatus);
        $this->assertSame(['known_shortener'], $shortenerReview->signals);
    }

    public function test_unsafe_urls_are_blocked(): void
    {
        $service = app(UrlReviewService::class);

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

    public function test_shortener_subdomains_require_manual_review(): void
    {
        $review = app(UrlReviewService::class)->review('https://www.tinyurl.com/project');

        $this->assertSame('needs_review', $review->trustStatus);
        $this->assertSame(['known_shortener'], $review->signals);
    }

    public function test_non_mvp_target_types_are_blocked(): void
    {
        $review = app(UrlReviewService::class)->review('https://example.com/file.zip', 'file_download');

        $this->assertSame('blocked', $review->trustStatus);
        $this->assertSame(['target_type_not_in_mvp'], $review->signals);
    }

    public function test_external_target_can_be_built_from_review_result(): void
    {
        $release = (new Release)->forceFill(['id' => 123]);
        $review = app(UrlReviewService::class)->review('https://create.roblox.com/store/asset/example');

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
        $review = app(UrlReviewService::class)->review('https://bit.ly/project');

        $target = ExternalTarget::makeFromReview($release, $review);

        $this->assertSame(DomainStatus::Suspicious, $target->domain_status);
    }

    public function test_blocked_review_results_cannot_be_built_as_external_targets(): void
    {
        $release = (new Release)->forceFill(['id' => 123]);
        $review = app(UrlReviewService::class)->review('javascript:alert(1)');

        $this->expectException(InvalidArgumentException::class);

        ExternalTarget::makeFromReview($release, $review);
    }
}
