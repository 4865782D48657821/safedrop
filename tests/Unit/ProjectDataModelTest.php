<?php

namespace Tests\Unit;

use App\Enums\AgeGroup;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
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
            'target_type' => 'project_page',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $project->refresh()->load('creator', 'latestPublicRelease.approvedExternalTargets');

        $this->assertSame('Creator', $project->creator->name);
        $this->assertSame(['building'], $project->categories);
        $this->assertSame(['minecraft', 'resource-pack'], $project->tags);
        $this->assertSame(['minecraft_versions' => ['1.21']], $project->latestPublicRelease->compatibility);
        $this->assertSame('modrinth.com', $project->latestPublicRelease->approvedExternalTargets->first()->target_domain);
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
        $this->assertSame('HTTPS://example.com/project', $uppercaseSchemeTarget->safeDestinationUrl());
        $this->assertNull($unsafeTarget->safeDestinationUrl());
        $this->assertNull($privateTarget->safeDestinationUrl());
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
            'target_type' => 'file_download',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        $this->assertCount(0, $release->approvedExternalTargets);
    }
}
