<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\ModerationDecision;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderation_queue_requires_moderator_role(): void
    {
        $this->get('/moderation')->assertRedirect(route('login'));

        $member = User::query()->create([
            'name' => 'Member',
            'email' => 'member@safedrop.test',
            'password' => 'safe-password',
        ]);

        $this->actingAs($member)->get('/moderation')->assertForbidden();
    }

    public function test_moderator_can_view_open_cases(): void
    {
        $case = ModerationCase::openForSubject(
            $this->project(),
            'project_metadata',
            'medium',
            'New creator project requires review.',
        );

        $this->actingAs($this->moderator())
            ->get('/moderation')
            ->assertOk()
            ->assertSee('Moderation Queue')
            ->assertSee($case->reason)
            ->assertSee('Project: Pending Project');
    }

    public function test_open_cases_are_idempotent_for_retrying_prechecks(): void
    {
        $project = $this->project();

        $first = ModerationCase::openForSubject(
            $project,
            'project_metadata',
            'medium',
            'New creator project requires review.',
        );
        $second = ModerationCase::openForSubject(
            $project,
            'project_metadata',
            'high',
            'Risk was escalated by a retry.',
        );

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('moderation_cases', 1);
        $this->assertSame('high', $second->risk_level);
        $this->assertSame('Risk was escalated by a retry.', $second->reason);
    }

    public function test_resolved_case_history_does_not_block_future_open_case(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->project();
        $first = ModerationCase::openForSubject($project, 'project_metadata', 'medium');

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $first), ['action' => 'approve'])
            ->assertRedirect(route('moderation.index'));

        $second = ModerationCase::openForSubject(
            $project,
            'project_metadata',
            'high',
            'Material project changes require review.',
        );

        $this->assertNotSame($first->id, $second->id);

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $second), ['action' => 'block'])
            ->assertRedirect(route('moderation.index'));

        $this->assertDatabaseCount('moderation_cases', 2);
        $this->assertDatabaseCount('moderation_decisions', 2);
    }

    public function test_moderator_approval_updates_project_status_and_records_decision(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->project();
        $case = ModerationCase::query()->create([
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'category' => 'project_metadata',
            'risk_level' => 'medium',
            'reason' => 'Review project metadata.',
        ]);

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $case), [
                'action' => 'approve',
                'note' => 'Looks ready.',
            ])
            ->assertRedirect(route('moderation.index'));

        $project->refresh();
        $case->refresh();

        $this->assertSame('approved', $project->moderation_status);
        $this->assertSame('resolved', $case->status);
        $this->assertNotNull($case->reviewed_at);
        $this->assertDatabaseHas(ModerationDecision::class, [
            'moderation_case_id' => $case->id,
            'action' => 'approve',
            'note' => 'Looks ready.',
        ]);
        $this->assertSame('Moderator', ModerationDecision::query()->firstOrFail()->moderator_snapshot['name']);
    }

    public function test_needs_review_keeps_case_open_and_records_decision(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->project();
        $case = ModerationCase::query()->create([
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'category' => 'project_metadata',
            'open_key' => ModerationCase::openKey($project, 'project_metadata'),
            'risk_level' => 'medium',
        ]);

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $case), ['action' => 'needs_review'])
            ->assertRedirect(route('moderation.index'));

        $this->assertSame('open', $case->fresh()->status);
        $this->assertDatabaseHas(ModerationDecision::class, [
            'moderation_case_id' => $case->id,
            'action' => 'needs_review',
        ]);
    }

    public function test_non_moderator_cannot_decide_cases(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = ModerationCase::query()->create([
            'subject_type' => Project::class,
            'subject_id' => $this->project()->id,
            'category' => 'project_metadata',
            'risk_level' => 'medium',
        ]);

        $this->actingAs($this->creator())
            ->post(route('moderation.decide', $case), ['action' => 'approve'])
            ->assertForbidden();

        $this->assertSame('open', $case->fresh()->status);
        $this->assertDatabaseCount('moderation_decisions', 0);
    }

    public function test_blocking_external_target_removes_redirect_access_and_audits_decision(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project, $target] = $this->reviewedProjectWithTarget();
        $case = ModerationCase::query()->create([
            'subject_type' => ExternalTarget::class,
            'subject_id' => $target->id,
            'category' => 'external_target',
            'risk_level' => 'high',
            'reason' => 'Suspicious target change.',
        ]);

        $this->get("/go/{$project->slug}")->assertOk();

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $case), ['action' => 'block'])
            ->assertRedirect(route('moderation.index'));

        $target->refresh();

        $this->assertSame('blocked', $target->trust_status);
        $this->assertSame(DomainStatus::Blocked, $target->domain_status);
        $this->get("/go/{$project->slug}")->assertForbidden();
        $this->assertDatabaseHas(ModerationDecision::class, [
            'moderation_case_id' => $case->id,
            'action' => 'block',
        ]);
    }

    public function test_resolved_cases_cannot_be_decided_again(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = ModerationCase::query()->create([
            'subject_type' => Project::class,
            'subject_id' => $this->project()->id,
            'category' => 'project_metadata',
            'status' => 'resolved',
            'risk_level' => 'medium',
        ]);

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $case), ['action' => 'approve'])
            ->assertStatus(409);
    }

    public function test_moderation_case_with_decisions_cannot_be_deleted_accidentally(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $case = ModerationCase::openForSubject($this->project(), 'project_metadata', 'medium');

        $this->actingAs($this->moderator())
            ->post(route('moderation.decide', $case), ['action' => 'approve'])
            ->assertRedirect(route('moderation.index'));

        $this->expectException(QueryException::class);

        DB::transaction(fn () => $case->fresh()->delete());
    }

    private function reviewedProjectWithTarget(): array
    {
        $project = Project::query()->create([
            'creator_id' => $this->creator()->id,
            'slug' => 'reviewed-project',
            'title' => 'Reviewed Project',
            'summary' => 'A project ready for public redirects.',
            'game' => 'minecraft',
            'project_type' => 'plugin',
            'publication_status' => 'published',
            'moderation_status' => 'approved',
        ]);

        $release = Release::query()->create([
            'project_id' => $project->id,
            'version' => '1.0.0',
            'published_at' => now(),
            'moderation_status' => 'approved',
        ]);

        $target = ExternalTarget::query()->create([
            'release_id' => $release->id,
            'original_url' => 'https://modrinth.com/plugin/example',
            'normalized_url' => 'https://modrinth.com/plugin/example',
            'redirect_chain' => ['https://modrinth.com/plugin/example'],
            'target_domain' => 'modrinth.com',
            'domain_status' => DomainStatus::Known,
            'target_type' => 'project_page',
            'reachability_status' => 'reachable',
            'trust_status' => 'approved',
        ]);

        return [$project, $target];
    }

    private function project(): Project
    {
        return Project::query()->create([
            'creator_id' => $this->creator()->id,
            'slug' => 'pending-project',
            'title' => 'Pending Project',
            'summary' => 'A project waiting for review.',
            'game' => 'roblox',
            'project_type' => 'resource',
            'publication_status' => 'published',
            'moderation_status' => 'pending',
        ]);
    }

    private function creator(): User
    {
        $creator = User::query()->firstOrCreate(
            ['email' => 'creator@safedrop.test'],
            [
                'name' => 'Creator',
                'password' => 'safe-password',
            ],
        );

        $creator->forceFill([
            'role' => UserRole::JuniorCreator,
            'age_group' => AgeGroup::Junior,
        ])->save();

        return $creator;
    }

    private function moderator(): User
    {
        $moderator = User::query()->firstOrCreate(
            ['email' => 'moderator@safedrop.test'],
            [
                'name' => 'Moderator',
                'password' => 'safe-password',
            ],
        );

        $moderator->forceFill([
            'role' => UserRole::Moderator,
            'age_group' => AgeGroup::AdultVerified,
        ])->save();

        return $moderator;
    }
}
