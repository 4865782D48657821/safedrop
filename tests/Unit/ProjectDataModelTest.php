<?php

namespace Tests\Unit;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\UrlReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_release_and_external_target_relations_and_casts(): void
    {
        $creator = User::query()->create([
            'name' => 'Creator',
            'email' => 'creator@safedrop.test',
            'password' => 'safe-password',
        ]);
        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        $project = Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'castle-pack',
            'title' => 'Castle Pack',
            'summary' => 'A compact resource pack for medieval builds.',
            'game' => 'minecraft',
            'project_type' => 'resource_pack',
            'categories' => ['building'],
            'tags' => ['minecraft', 'resource-pack'],
            'publication_status' => 'published',
            'moderation_status' => 'approved',
        ]);

        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'compatibility' => ['minecraft_versions' => ['1.21']],
            'published_at' => now(),
            'moderation_status' => 'approved',
        ]);

        ExternalTarget::query()->create([
            'release_id' => $release->id,
            'original_url' => 'https://modrinth.com/resourcepack/castle-pack',
            'normalized_url' => 'https://modrinth.com/resourcepack/castle-pack',
            'redirect_chain' => ['https://modrinth.com/resourcepack/castle-pack'],
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $project->refresh()->load('creator', 'latestPublicRelease.publicExternalTargets');

        $this->assertSame('Creator', $project->creator->name);
        $this->assertSame(['building'], $project->categories);
        $this->assertSame(['minecraft', 'resource-pack'], $project->tags);
        $this->assertSame(['minecraft_versions' => ['1.21']], $project->latestPublicRelease->compatibility);
        $this->assertSame('modrinth.com', $project->latestPublicRelease->publicExternalTargets->first()->target_domain);
    }

    public function test_external_target_only_exposes_http_and_https_destination_urls(): void
    {
        $httpsTarget = new ExternalTarget([
            'original_url' => 'https://example.com/project',
            'normalized_url' => 'https://example.com/project',
        ]);
        $uppercaseSchemeTarget = new ExternalTarget([
            'original_url' => 'HTTPS://example.com/project',
        ]);

        $unsafeTarget = new ExternalTarget([
            'original_url' => 'javascript:alert(1)',
            'normalized_url' => 'javascript:alert(1)',
        ]);
        $privateTarget = new ExternalTarget([
            'original_url' => 'https://127.0.0.1/project',
            'normalized_url' => 'https://127.0.0.1/project',
            'target_type' => 'project_page',
        ]);

        $this->assertSame('https://example.com/project', $httpsTarget->safeDestinationUrl());
        $this->assertSame('https://example.com/project', $uppercaseSchemeTarget->safeDestinationUrl());
        $this->assertNull($unsafeTarget->safeDestinationUrl());
        $this->assertNull($privateTarget->safeDestinationUrl());
    }

    public function test_external_target_public_destination_requires_publishable_statuses(): void
    {
        $target = new ExternalTarget([
            'original_url' => 'https://example.com/project',
            'normalized_url' => 'https://example.com/project',
            'target_domain' => 'example.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertSame('https://example.com/project', $target->publicDestinationUrl());

        $target->domain_status = DomainStatus::New;
        $this->assertNull($target->publicDestinationUrl());

        $target->domain_status = DomainStatus::Suspicious;
        $this->assertNull($target->publicDestinationUrl());

        $target->domain_status = DomainStatus::Blocked;
        $this->assertNull($target->publicDestinationUrl());
    }

    public function test_external_target_public_destination_requires_reviewed_domain_match(): void
    {
        $target = new ExternalTarget([
            'original_url' => 'https://modrinth.com/plugin/example',
            'normalized_url' => 'https://evil.example/project',
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertNull($target->publicDestinationUrl());
    }

    public function test_external_target_public_destination_uses_reviewed_same_domain_redirect_destination(): void
    {
        app()->instance(
            UrlReviewService::class,
            new UrlReviewService(
                fn (): array => ['93.184.216.34'],
                fn (string $url): ?string => $url === 'https://example.com/project' ? '/projects/current' : null,
            ),
        );

        try {
            $target = new ExternalTarget([
                'original_url' => 'https://example.com/project',
                'normalized_url' => 'https://example.com/project',
                'target_domain' => 'example.com',
                'domain_status' => DomainStatus::Known,
                'target_type' => 'project_page',
                'reachability_status' => 'reachable',
                'trust_status' => 'approved',
            ]);

            $this->assertSame('https://example.com/projects/current', $target->publicDestinationUrl());
        } finally {
            $this->fakeUrlReviewDns();
        }
    }

    public function test_external_target_public_destination_requires_current_dns_reachability(): void
    {
        app()->instance(UrlReviewService::class, new UrlReviewService(fn (string $host): array => $host === 'example.com' ? [] : ['93.184.216.34']));

        try {
            $target = new ExternalTarget([
                'original_url' => 'https://example.com/project',
                'normalized_url' => 'https://example.com/project',
                'target_domain' => 'example.com',
                'domain_status' => DomainStatus::Known,
                'target_type' => 'project_page',
                'reachability_status' => 'reachable',
                'trust_status' => 'approved',
            ]);

            $this->assertNull($target->publicDestinationUrl());
        } finally {
            $this->fakeUrlReviewDns();
        }
    }

    public function test_external_target_public_destination_requires_signal_free_current_review(): void
    {
        $target = new ExternalTarget([
            'original_url' => 'http://example.com/project',
            'normalized_url' => 'http://example.com/project',
            'target_domain' => 'example.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertNull($target->publicDestinationUrl());
    }

    public function test_configured_domain_statuses_are_castable(): void
    {
        foreach (config('safedrop.domain_statuses') as $status) {
            $target = new ExternalTarget([
                'domain_status' => $status,
            ]);

            $this->assertInstanceOf(DomainStatus::class, $target->domain_status);
        }
    }

    public function test_unreachable_reachability_is_exposed_as_effective_domain_status(): void
    {
        $target = new ExternalTarget([
            'domain_status' => DomainStatus::Known,
            'reachability_status' => 'unreachable',
        ]);

        $this->assertSame('unreachable', $target->effectiveDomainStatus());
        $this->assertNull($target->publicDestinationUrl());
    }

    public function test_approved_external_targets_are_limited_to_project_pages(): void
    {
        $creator = User::query()->create([
            'name' => 'Creator',
            'email' => 'creator-two@safedrop.test',
            'password' => 'safe-password',
        ]);

        $project = Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'toolkit',
            'title' => 'Toolkit',
            'summary' => 'A toolkit project.',
            'game' => 'roblox',
            'project_type' => 'resource',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
        ]);

        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'published_at' => now(),
            'moderation_status' => 'approved',
        ]);

        ExternalTarget::query()->create([
            'release_id' => $release->id,
            'original_url' => 'https://example.com/file.zip',
            'target_domain' => 'example.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'file_download',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertCount(0, $release->publicExternalTargets);
    }
}
