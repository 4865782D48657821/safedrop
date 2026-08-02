<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\ProjectRating;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_rate_update_and_remove_public_project_feedback(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        $this->actingAs($user)
            ->post(route('projects.rating.store', $project->slug), ['signal' => ProjectRating::HELPFUL])
            ->assertRedirect();

        $this->assertDatabaseHas('project_ratings', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'signal' => ProjectRating::HELPFUL,
        ]);

        $this->actingAs($user)
            ->post(route('projects.rating.store', $project->slug), ['signal' => ProjectRating::NOT_HELPFUL])
            ->assertRedirect();

        $this->assertSame(1, ProjectRating::query()->where('user_id', $user->id)->where('project_id', $project->id)->count());
        $this->assertDatabaseHas('project_ratings', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'signal' => ProjectRating::NOT_HELPFUL,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.rating.destroy', $project->slug))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_ratings', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_project_page_shows_rating_counts_and_current_rating(): void
    {
        $user = $this->member();
        $project = $this->publicProject();
        $user->projectRatings()->create([
            'project_id' => $project->id,
            'signal' => ProjectRating::HELPFUL,
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee('Project Feedback')
            ->assertSee('1 helpful')
            ->assertSee('0 not helpful')
            ->assertSee('Remove feedback');
    }

    public function test_guest_cannot_rate_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->publicProject();

        $this->post(route('projects.rating.store', $project->slug), ['signal' => ProjectRating::HELPFUL])
            ->assertRedirect(route('login'));
    }

    public function test_rating_rejects_invalid_signal(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        $this->actingAs($user)
            ->from(route('projects.show', $project->slug))
            ->post(route('projects.rating.store', $project->slug), ['signal' => 'five_stars'])
            ->assertRedirect(route('projects.show', $project->slug))
            ->assertSessionHasErrors('signal');

        $this->assertDatabaseMissing('project_ratings', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_database_rejects_direct_invalid_rating_signal_writes(): void
    {
        $user = $this->member();
        $project = $this->publicProject();

        $this->expectException(QueryException::class);

        $user->projectRatings()->create([
            'project_id' => $project->id,
            'signal' => 'five_stars',
        ]);
    }

    public function test_user_cannot_rate_non_public_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject([
            'slug' => 'hidden-project',
            'title' => 'Hidden Project',
            'moderation_status' => 'blocked',
        ]);

        $this->actingAs($user)
            ->post(route('projects.rating.store', $project->slug), ['signal' => ProjectRating::HELPFUL])
            ->assertNotFound();

        $this->assertDatabaseMissing('project_ratings', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_project_rating_submission_is_rate_limited(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->actingAs($user)
                ->post(route('projects.rating.store', $project->slug), [
                    'signal' => $attempt % 2 === 0 ? ProjectRating::HELPFUL : ProjectRating::NOT_HELPFUL,
                ])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('projects.rating.store', $project->slug), ['signal' => ProjectRating::HELPFUL])
            ->assertTooManyRequests();
    }

    public function test_project_rating_removal_is_rate_limited(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->actingAs($user)
                ->delete(route('projects.rating.destroy', $project->slug))
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->delete(route('projects.rating.destroy', $project->slug))
            ->assertTooManyRequests();
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
