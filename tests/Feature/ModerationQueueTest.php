<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\ContentReport;
use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\ModerationDecision;
use App\Models\Project;
use App\Models\Release;
use App\Models\RightsCase;
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

    public function test_guest_can_submit_project_report_for_moderation(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();

        $this->post(route('projects.reports.store', $project->slug), [
            'reason' => 'unsafe_link',
            'details' => 'The destination appears to send players to an unsafe download.',
            'reporter_email' => 'parent@example.test',
        ])->assertRedirect(route('projects.show', $project->slug));

        $report = ContentReport::query()->firstOrFail();

        $this->assertSame($project->id, $report->project_id);
        $this->assertNull($report->reporter_id);
        $this->assertSame('parent@example.test', $report->reporter_email);
        $this->assertSame($project->slug, $report->project_snapshot['slug']);
        $this->assertDatabaseHas(ModerationCase::class, [
            'subject_type' => ContentReport::class,
            'subject_id' => $report->id,
            'category' => 'report',
            'risk_level' => 'high',
            'status' => 'open',
        ]);

        $this->actingAs($this->moderator())
            ->get(route('moderation.index'))
            ->assertOk()
            ->assertSee('Report: Reviewed Project');
    }

    public function test_duplicate_project_report_reuses_open_moderation_case(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();
        $payload = [
            'reason' => 'unsafe_link',
            'details' => 'The destination appears to send players to an unsafe download.',
            'reporter_email' => 'parent@example.test',
        ];

        $this->post(route('projects.reports.store', $project->slug), $payload)
            ->assertRedirect(route('projects.show', $project->slug));
        $this->post(route('projects.reports.store', $project->slug), $payload)
            ->assertRedirect(route('projects.show', $project->slug));

        $this->assertDatabaseCount('content_reports', 1);
        $this->assertDatabaseCount('moderation_cases', 1);
    }

    public function test_project_report_submission_is_rate_limited(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();
        $route = route('projects.reports.store', $project->slug);

        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->post($route, [
                    'reason' => 'unsafe_link',
                    'details' => "The destination appears unsafe enough to report {$i}.",
                    'reporter_email' => 'parent@example.test',
                ])
                ->assertRedirect(route('projects.show', $project->slug));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post($route, [
                'reason' => 'unsafe_link',
                'details' => 'The destination appears unsafe after too many reports.',
                'reporter_email' => 'parent@example.test',
            ])
            ->assertTooManyRequests();
    }

    public function test_report_keeps_project_snapshot_after_project_delete(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();

        $this->post(route('projects.reports.store', $project->slug), [
            'reason' => 'unsafe_link',
            'details' => 'The destination appears to send players to an unsafe download.',
            'reporter_email' => 'parent@example.test',
        ])->assertRedirect(route('projects.show', $project->slug));

        $project->delete();

        $report = ContentReport::query()->firstOrFail();

        $this->assertNull($report->project_id);
        $this->assertSame('reviewed-project', $report->project_snapshot['slug']);
        $this->assertDatabaseCount('moderation_cases', 1);
    }

    public function test_project_report_validation_rejects_invalid_input(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();

        $this->from(route('projects.show', $project->slug))
            ->post(route('projects.reports.store', $project->slug), [
                'reason' => 'not_allowed',
                'details' => 'short',
                'reporter_email' => 'not-an-email',
            ])
            ->assertRedirect(route('projects.show', $project->slug))
            ->assertSessionHasErrors(['reason', 'details', 'reporter_email']);

        $this->assertDatabaseCount('content_reports', 0);
        $this->assertDatabaseCount('moderation_cases', 0);
    }

    public function test_rights_case_can_be_submitted_for_moderation(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();

        $this->get(route('rights.create'))
            ->assertOk()
            ->assertSee('Rights Case')
            ->assertSee($project->title);

        $this->post(route('rights.store'), [
            'project_id' => $project->id,
            'claimant_name' => 'Rights Owner',
            'claimant_email' => 'rights@example.test',
            'claim_type' => 'copyright',
            'details' => 'This project appears to reuse copyrighted material without permission.',
        ])->assertRedirect(route('rights.create'));

        $case = RightsCase::query()->firstOrFail();

        $this->assertSame($project->id, $case->project_id);
        $this->assertDatabaseHas(ModerationCase::class, [
            'subject_type' => RightsCase::class,
            'subject_id' => $case->id,
            'category' => 'rights_case',
            'risk_level' => 'high',
            'status' => 'open',
        ]);

        $this->actingAs($this->moderator())
            ->get(route('moderation.index'))
            ->assertOk()
            ->assertSee('Rights case: copyright');
    }

    public function test_duplicate_rights_case_reuses_open_moderation_case(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();
        $payload = [
            'project_id' => $project->id,
            'claimant_name' => 'Rights Owner',
            'claimant_email' => 'rights@example.test',
            'claim_type' => 'copyright',
            'details' => 'This project appears to reuse copyrighted material without permission.',
        ];

        $this->post(route('rights.store'), $payload)->assertRedirect(route('rights.create'));
        $this->post(route('rights.store'), $payload)->assertRedirect(route('rights.create'));

        $this->assertDatabaseCount('rights_cases', 1);
        $this->assertDatabaseCount('moderation_cases', 1);
    }

    public function test_rights_case_submission_is_rate_limited(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        [$project] = $this->reviewedProjectWithTarget();
        $route = route('rights.store');

        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
                ->post($route, [
                    'project_id' => $project->id,
                    'claimant_name' => 'Rights Owner',
                    'claimant_email' => 'rights@example.test',
                    'claim_type' => 'copyright',
                    'details' => "This project appears to reuse copyrighted material without permission {$i}.",
                ])
                ->assertRedirect(route('rights.create'));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->post($route, [
                'project_id' => $project->id,
                'claimant_name' => 'Rights Owner',
                'claimant_email' => 'rights@example.test',
                'claim_type' => 'copyright',
                'details' => 'This project appears to reuse copyrighted material after too many cases.',
            ])
            ->assertTooManyRequests();
    }

    public function test_rights_case_validation_rejects_invalid_input(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->from(route('rights.create'))
            ->post(route('rights.store'), [
                'project_id' => 999,
                'claimant_name' => '',
                'claimant_email' => 'invalid',
                'claim_type' => 'not_allowed',
                'details' => 'too short',
            ])
            ->assertRedirect(route('rights.create'))
            ->assertSessionHasErrors(['project_id', 'claimant_name', 'claimant_email', 'claim_type', 'details']);

        $this->assertDatabaseCount('rights_cases', 0);
        $this->assertDatabaseCount('moderation_cases', 0);
    }

    public function test_rights_case_rejects_non_public_project_reference(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $project = $this->project();

        $this->from(route('rights.create'))
            ->post(route('rights.store'), [
                'project_id' => $project->id,
                'claimant_name' => 'Rights Owner',
                'claimant_email' => 'rights@example.test',
                'claim_type' => 'copyright',
                'details' => 'This claim includes enough detail to pass the minimum validation.',
            ])
            ->assertRedirect(route('rights.create'))
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('rights_cases', 0);
        $this->assertDatabaseCount('moderation_cases', 0);
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

    public function test_moderator_decision_updates_report_and_rights_case_statuses(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $report = $this->contentReport([
            'reason' => 'spam_or_abuse',
            'details' => 'This project is being promoted with misleading abuse reports.',
        ]);
        $reportCase = ModerationCase::openForSubject($report, 'report', 'medium');

        $rightsCase = $this->rightsCase([
            'claimant_name' => 'Rights Owner',
            'claimant_email' => 'rights@example.test',
            'claim_type' => 'trademark',
            'details' => 'The title appears to misuse a trademarked server brand.',
        ]);
        $rightsModerationCase = ModerationCase::openForSubject($rightsCase, 'rights_case', 'high');

        $moderator = $this->moderator();

        $this->actingAs($moderator)
            ->post(route('moderation.decide', $reportCase), ['action' => 'approve'])
            ->assertRedirect(route('moderation.index'));
        $this->actingAs($moderator)
            ->post(route('moderation.decide', $rightsModerationCase), ['action' => 'block'])
            ->assertRedirect(route('moderation.index'));

        $this->assertSame('actioned', $report->fresh()->status);
        $this->assertSame('rejected', $rightsCase->fresh()->status);
        $this->assertDatabaseHas(ModerationDecision::class, [
            'moderation_case_id' => $reportCase->id,
            'action' => 'approve',
        ]);
        $this->assertDatabaseHas(ModerationDecision::class, [
            'moderation_case_id' => $rightsModerationCase->id,
            'action' => 'block',
        ]);
    }

    public function test_moderator_can_dismiss_report_and_action_rights_case(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $report = $this->contentReport([
            'reason' => 'other',
            'details' => 'The report does not contain actionable information.',
        ]);
        $reportCase = ModerationCase::openForSubject($report, 'report', 'medium');

        $rightsCase = $this->rightsCase([
            'claimant_name' => 'Rights Owner',
            'claimant_email' => 'rights@example.test',
            'claim_type' => 'ownership_dispute',
            'details' => 'The claimant has provided enough ownership context for action.',
        ]);
        $rightsModerationCase = ModerationCase::openForSubject($rightsCase, 'rights_case', 'high');

        $moderator = $this->moderator();

        $this->actingAs($moderator)
            ->post(route('moderation.decide', $reportCase), ['action' => 'block'])
            ->assertRedirect(route('moderation.index'));
        $this->actingAs($moderator)
            ->post(route('moderation.decide', $rightsModerationCase), ['action' => 'approve'])
            ->assertRedirect(route('moderation.index'));

        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertSame('actioned', $rightsCase->fresh()->status);
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

    private function contentReport(array $overrides = []): ContentReport
    {
        [$project] = $this->reviewedProjectWithTarget();
        $attributes = array_merge([
            'project_id' => $project->id,
            'reporter_email' => 'reporter@example.test',
            'reason' => 'unsafe_link',
            'details' => 'The destination appears to send players to an unsafe download.',
            'project_snapshot' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
            ],
        ], $overrides);
        $attributes['fingerprint'] = hash('sha256', implode('|', [
            $attributes['project_id'] ?? '',
            $attributes['reason'],
            strtolower((string) ($attributes['reporter_email'] ?? '')),
            trim($attributes['details']),
        ]));

        return ContentReport::query()->create($attributes);
    }

    private function rightsCase(array $overrides = []): RightsCase
    {
        $attributes = array_merge([
            'claimant_name' => 'Rights Owner',
            'claimant_email' => 'rights@example.test',
            'claim_type' => 'copyright',
            'details' => 'This project appears to reuse copyrighted material without permission.',
        ], $overrides);
        $attributes['claimant_email'] = strtolower($attributes['claimant_email']);
        $attributes['fingerprint'] = hash('sha256', implode('|', [
            $attributes['project_id'] ?? '',
            $attributes['claimant_email'],
            $attributes['claim_type'],
            trim($attributes['details']),
        ]));

        return RightsCase::query()->create($attributes);
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
