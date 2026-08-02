<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_and_remove_public_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        $this->actingAs($user)
            ->post(route('projects.saved.store', $project->slug))
            ->assertRedirect();

        $this->assertDatabaseHas('saved_projects', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee('Saved');

        $this->actingAs($user)
            ->delete(route('projects.saved.destroy', $project->slug))
            ->assertRedirect();

        $this->assertDatabaseMissing('saved_projects', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_guest_cannot_save_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->publicProject();

        $this->post(route('projects.saved.store', $project->slug))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_save_non_public_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject([
            'slug' => 'pending-project',
            'title' => 'Pending Project',
            'moderation_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('projects.saved.store', $project->slug))
            ->assertNotFound();

        $this->assertDatabaseMissing('saved_projects', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_account_page_lists_only_saved_projects_that_remain_public(): void
    {
        $user = $this->member();
        $publicProject = $this->publicProject();
        $hiddenProject = $this->publicProject([
            'slug' => 'hidden-after-save',
            'title' => 'Hidden After Save',
            'moderation_status' => 'blocked',
        ]);

        $user->savedProjects()->syncWithoutDetaching([
            $publicProject->id,
            $hiddenProject->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Saved Projects')
            ->assertSee('SkyForge Build Tools')
            ->assertSee('Unavailable Saved Projects')
            ->assertSee('Unavailable project')
            ->assertDontSee('Hidden After Save');
    }

    public function test_user_can_remove_unavailable_saved_project_without_seeing_project_details(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $hiddenProject = $this->publicProject([
            'slug' => 'hidden-after-save',
            'title' => 'Hidden After Save',
            'moderation_status' => 'blocked',
        ]);
        $user->savedProjects()->syncWithoutDetaching([$hiddenProject->id]);
        $savedProjectId = $user->savedProjects()->firstOrFail()->pivot->id;

        $this->actingAs($user)
            ->delete(route('saved-projects.destroy', $savedProjectId))
            ->assertRedirect();

        $this->assertDatabaseMissing('saved_projects', [
            'user_id' => $user->id,
            'project_id' => $hiddenProject->id,
        ]);
    }

    public function test_user_cannot_remove_another_users_saved_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $owner = $this->member();
        $otherUser = User::query()->create([
            'name' => 'Other Member',
            'email' => 'other-member@safedrop.test',
            'password' => 'safe-password',
        ]);
        $hiddenProject = $this->publicProject([
            'slug' => 'hidden-after-save',
            'title' => 'Hidden After Save',
            'moderation_status' => 'blocked',
        ]);
        $owner->savedProjects()->syncWithoutDetaching([$hiddenProject->id]);
        $savedProjectId = $owner->savedProjects()->firstOrFail()->pivot->id;

        $this->actingAs($otherUser)
            ->delete(route('saved-projects.destroy', $savedProjectId))
            ->assertRedirect();

        $this->assertDatabaseHas('saved_projects', [
            'user_id' => $owner->id,
            'project_id' => $hiddenProject->id,
        ]);
    }

    private function member(): User
    {
        return User::query()->create([
            'name' => 'Member',
            'email' => 'member@safedrop.test',
            'password' => 'safe-password',
        ]);
    }

    private function creator(string $slug): User
    {
        $creator = User::query()->create([
            'name' => "Creator {$slug}",
            'email' => "creator-{$slug}@safedrop.test",
            'password' => 'safe-password',
        ]);

        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        return $creator;
    }

    private function publicProject(array $overrides = []): Project
    {
        $attributes = array_merge([
            'slug' => 'skyforge-build-tools',
            'title' => 'SkyForge Build Tools',
            'summary' => 'Server utilities for protected build zones and collaborative survival maps.',
            'description' => 'A moderated Minecraft server plugin starter project.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'tags' => ['servers', 'tools'],
            'language' => 'en',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
            'age_rating' => '12+',
        ], $overrides);

        $project = Project::query()->create([
            'creator_id' => $this->creator($attributes['slug'])->id,
        ] + $attributes);

        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'published_at' => now(),
            'moderation_status' => 'approved',
        ]);

        ExternalTarget::query()->create([
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
        ]);

        return $project;
    }
}
