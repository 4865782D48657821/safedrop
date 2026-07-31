<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_mvp_discovery_projects(): void
    {
        $this->seedReviewedProject();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Safedrop');
        $response->assertSee('Minecraft');
        $response->assertSee('Roblox');
        $response->assertSee('SkyForge Build Tools');
    }

    public function test_project_page_exposes_reviewed_external_destination(): void
    {
        $this->seedReviewedProject();

        $response = $this->get('/projects/skyforge-build-tools');

        $response->assertOk();
        $response->assertSee('Safety Status');
        $response->assertSee('Domain status');
        $response->assertSee('modrinth.com');
        $response->assertSee('Revenue ads are disabled for this project.');
    }

    public function test_unapproved_projects_are_not_publicly_visible(): void
    {
        $creator = $this->creator();

        Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'hidden-project',
            'title' => 'Hidden Project',
            'summary' => 'This project still needs moderation.',
            'game' => 'minecraft',
            'project_type' => 'mod',
            'publication_status' => 'published',
            'moderation_status' => 'pending',
        ]);

        $this->get('/')->assertDontSee('Hidden Project');
        $this->get('/projects/hidden-project')->assertNotFound();
        $this->get('/go/hidden-project')->assertNotFound();
    }

    public function test_unpublished_projects_are_not_publicly_visible(): void
    {
        $creator = $this->creator();

        Project::query()->create([
            'creator_id' => $creator->id,
            'slug' => 'draft-project',
            'title' => 'Draft Project',
            'summary' => 'This project is not published.',
            'game' => 'roblox',
            'project_type' => 'experience',
            'publication_status' => 'draft',
            'moderation_status' => 'approved',
        ]);

        $this->get('/')->assertDontSee('Draft Project');
        $this->get('/projects/draft-project')->assertNotFound();
        $this->get('/go/draft-project')->assertNotFound();
    }

    public function test_redirect_preview_requires_approved_reachable_target(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'trust_status' => 'pending',
        ]);

        $this->get("/go/{$project->slug}")->assertForbidden();
    }

    public function test_redirect_preview_rejects_unreachable_target(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'reachability_status' => 'unreachable',
        ]);

        $this->get("/go/{$project->slug}")->assertForbidden();
    }

    public function test_redirect_preview_rejects_new_domain_until_domain_review_passes(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'domain_status' => DomainStatus::New,
        ]);

        $this->get("/go/{$project->slug}")->assertForbidden();
    }

    public function test_redirect_preview_rejects_suspicious_or_blocked_domain_statuses(): void
    {
        $suspiciousProject = $this->seedReviewedProject(
            slug: 'suspicious-project',
            targetOverrides: ['domain_status' => DomainStatus::Suspicious],
        );
        $blockedProject = $this->seedReviewedProject(
            slug: 'blocked-project',
            targetOverrides: ['domain_status' => DomainStatus::Blocked],
        );

        $this->get("/go/{$suspiciousProject->slug}")->assertForbidden();
        $this->get("/go/{$blockedProject->slug}")->assertForbidden();
    }

    public function test_redirect_preview_rejects_approved_target_with_unsafe_scheme(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'original_url' => 'javascript:alert(1)',
            'normalized_url' => 'javascript:alert(1)',
        ]);

        $this->get("/go/{$project->slug}")->assertForbidden();
    }

    public function test_redirect_preview_rejects_target_url_that_drifts_from_reviewed_domain(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'target_domain' => 'modrinth.com',
            'normalized_url' => 'https://evil.example/project',
        ]);

        $this->get("/go/{$project->slug}")->assertForbidden();
    }

    public function test_redirect_preview_rejects_non_mvp_target_type(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'target_type' => 'file_download',
        ]);

        $this->get("/go/{$project->slug}")->assertForbidden();
    }

    public function test_redirect_preview_allows_normalized_url_fallback_to_original_url(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'normalized_url' => null,
            'original_url' => 'https://modrinth.com/plugin/fallback-example',
        ]);

        $this->get("/go/{$project->slug}")
            ->assertOk()
            ->assertSee('https://modrinth.com/plugin/fallback-example');
    }

    public function test_project_page_hides_continue_link_without_safe_approved_target(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'trust_status' => 'blocked',
        ]);

        $this->get("/projects/{$project->slug}")
            ->assertOk()
            ->assertSee('does not have an approved external destination')
            ->assertDontSee('Continue safely');
    }

    public function test_project_page_hides_continue_link_for_unreviewed_domain_status(): void
    {
        $project = $this->seedReviewedProject(targetOverrides: [
            'domain_status' => DomainStatus::New,
        ]);

        $this->get("/projects/{$project->slug}")
            ->assertOk()
            ->assertSee('does not have an approved external destination')
            ->assertDontSee('Continue safely');
    }

    private function seedReviewedProject(array $targetOverrides = [], string $slug = 'skyforge-build-tools'): Project
    {
        $project = Project::query()->create([
            'creator_id' => $this->creator()->id,
            'slug' => $slug,
            'title' => 'SkyForge Build Tools',
            'summary' => 'Server utilities for protected build zones and collaborative survival maps.',
            'description' => 'A moderated Minecraft server plugin starter project for protected build zones.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'categories' => ['servers', 'moderation'],
            'tags' => ['servers', 'tools', 'moderated'],
            'language' => 'en',
            'license' => 'custom',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
            'age_rating' => '12+',
        ]);

        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'compatibility' => ['minecraft_versions' => ['1.21']],
            'published_at' => now(),
            'moderation_status' => 'approved',
        ]);

        ExternalTarget::query()->create(array_merge([
            'release_id' => $release->id,
            'original_url' => 'https://modrinth.com/plugin/example',
            'normalized_url' => 'https://modrinth.com/plugin/example',
            'redirect_chain' => ['https://modrinth.com/plugin/example'],
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'last_checked_at' => now(),
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ], $targetOverrides));

        return $project;
    }

    private function creator(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'creator@safedrop.test'],
            [
                'name' => 'Blocksmith Studio',
                'password' => 'safe-password',
            ],
        )->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ]);
    }
}
