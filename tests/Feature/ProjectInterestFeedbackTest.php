<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\ProjectInterestFeedback;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectInterestFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_mark_and_remove_not_interested_feedback(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        $this->actingAs($user)
            ->post(route('projects.interest-feedback.store', $project->slug))
            ->assertRedirect();

        $this->assertDatabaseHas('project_interest_feedback', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'signal' => ProjectInterestFeedback::NOT_INTERESTED,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.interest-feedback.destroy', $project->slug))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_interest_feedback', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_project_page_shows_private_interest_feedback_state(): void
    {
        $user = $this->member();
        $project = $this->publicProject();
        $user->projectInterestFeedback()->create([
            'project_id' => $project->id,
            'signal' => ProjectInterestFeedback::NOT_INTERESTED,
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project->slug))
            ->assertOk()
            ->assertSee('Feed Preference')
            ->assertSee('not interesting for your feed')
            ->assertDontSee('0 not interested');
    }

    public function test_account_page_lists_unavailable_interest_feedback_without_project_details(): void
    {
        $user = $this->member();
        $hiddenProject = $this->publicProject([
            'slug' => 'hidden-interest-feedback',
            'title' => 'Hidden Interest Feedback',
            'moderation_status' => 'blocked',
        ]);

        $user->projectInterestFeedback()->create([
            'project_id' => $hiddenProject->id,
            'signal' => ProjectInterestFeedback::NOT_INTERESTED,
        ]);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Unavailable Feed Preferences')
            ->assertSee('Unavailable project')
            ->assertDontSee('Hidden Interest Feedback');
    }

    public function test_user_can_remove_unavailable_interest_feedback_without_seeing_project_details(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $hiddenProject = $this->publicProject([
            'slug' => 'hidden-interest-feedback',
            'title' => 'Hidden Interest Feedback',
            'moderation_status' => 'blocked',
        ]);

        $feedback = $user->projectInterestFeedback()->create([
            'project_id' => $hiddenProject->id,
            'signal' => ProjectInterestFeedback::NOT_INTERESTED,
        ]);

        $this->actingAs($user)
            ->delete(route('interest-feedback.destroy', $feedback->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_interest_feedback', [
            'user_id' => $user->id,
            'project_id' => $hiddenProject->id,
        ]);
    }

    public function test_user_cannot_remove_another_users_interest_feedback(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $owner = $this->member();
        $otherUser = User::query()->create([
            'name' => 'Other Member',
            'email' => 'other-member@safedrop.test',
            'password' => 'safe-password',
        ]);
        $hiddenProject = $this->publicProject([
            'slug' => 'hidden-interest-feedback',
            'title' => 'Hidden Interest Feedback',
            'moderation_status' => 'blocked',
        ]);

        $feedback = $owner->projectInterestFeedback()->create([
            'project_id' => $hiddenProject->id,
            'signal' => ProjectInterestFeedback::NOT_INTERESTED,
        ]);

        $this->actingAs($otherUser)
            ->delete(route('interest-feedback.destroy', $feedback->id))
            ->assertRedirect();

        $this->assertDatabaseHas('project_interest_feedback', [
            'user_id' => $owner->id,
            'project_id' => $hiddenProject->id,
        ]);
    }

    public function test_guest_cannot_set_interest_feedback(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->publicProject();

        $this->post(route('projects.interest-feedback.store', $project->slug))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_set_interest_feedback_for_non_public_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject([
            'slug' => 'hidden-project',
            'title' => 'Hidden Project',
            'moderation_status' => 'blocked',
        ]);

        $this->actingAs($user)
            ->post(route('projects.interest-feedback.store', $project->slug))
            ->assertNotFound();

        $this->assertDatabaseMissing('project_interest_feedback', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_junior_user_cannot_set_interest_feedback_for_adult_rated_project(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject([
            'slug' => 'adult-rated-project',
            'title' => 'Adult Rated Project',
            'age_rating' => '18+',
        ]);

        $this->actingAs($user)
            ->post(route('projects.interest-feedback.store', $project->slug))
            ->assertNotFound();

        $this->assertDatabaseMissing('project_interest_feedback', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_database_rejects_direct_invalid_interest_feedback_signal_writes(): void
    {
        $user = $this->member();
        $project = $this->publicProject();

        $this->expectException(QueryException::class);

        $user->projectInterestFeedback()->create([
            'project_id' => $project->id,
            'signal' => 'boost',
        ]);
    }

    public function test_interest_feedback_submission_and_removal_are_rate_limited(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->member();
        $project = $this->publicProject();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->actingAs($user)
                ->post(route('projects.interest-feedback.store', $project->slug))
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('projects.interest-feedback.store', $project->slug))
            ->assertTooManyRequests();

        $otherUser = User::query()->create([
            'name' => 'Other Member',
            'email' => 'other-member@safedrop.test',
            'password' => 'safe-password',
        ]);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->actingAs($otherUser)
                ->delete(route('projects.interest-feedback.destroy', $project->slug))
                ->assertRedirect();
        }

        $this->actingAs($otherUser)
            ->delete(route('projects.interest-feedback.destroy', $project->slug))
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
