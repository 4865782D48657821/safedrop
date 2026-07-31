<?php

namespace App\Http\Controllers;

use App\Enums\DomainStatus;
use App\Models\ContentReport;
use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\Release;
use App\Models\RightsCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->canModerateContent(), 403);

        $cases = ModerationCase::query()
            ->with(['subject', 'reviewer'])
            ->where('status', 'open')
            ->latest()
            ->get();

        return view('moderation.index', [
            'cases' => $cases,
        ]);
    }

    public function decide(Request $request, ModerationCase $case): RedirectResponse
    {
        abort_unless($request->user()?->canModerateContent(), 403);

        $data = $request->validate([
            'action' => ['required', Rule::in(config('safedrop.moderation_actions'))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($case, $request, $data): void {
            $case = ModerationCase::query()
                ->whereKey($case->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($case->isOpen(), 409);

            $case->load('subject');
            $subject = $case->subject;
            abort_unless($subject, 404);

            $before = $this->statusSnapshot($subject);

            $this->applyDecision($subject, $data['action']);

            $case->forceFill([
                'status' => $this->caseStatusFor($data['action']),
                'open_key' => $data['action'] === 'needs_review' ? $case->open_key : null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            $case->decisions()->create([
                'moderator_id' => $request->user()->id,
                'action' => $data['action'],
                'note' => $data['note'] ?? null,
                'moderator_snapshot' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                ],
                'status_snapshot' => [
                    'before' => $before,
                    'after' => $this->statusSnapshot($subject->fresh()),
                ],
            ]);
        });

        return redirect()->route('moderation.index');
    }

    private function applyDecision(object $subject, string $action): void
    {
        match (true) {
            $subject instanceof ContentReport => $this->applyReportDecision($subject, $action),
            $subject instanceof RightsCase => $this->applyRightsCaseDecision($subject, $action),
            $subject instanceof Project => $this->applyProjectDecision($subject, $action),
            $subject instanceof Release => $this->applyReleaseDecision($subject, $action),
            $subject instanceof ExternalTarget => $this->applyExternalTargetDecision($subject, $action),
            default => abort(422),
        };
    }

    private function caseStatusFor(string $action): string
    {
        return $action === 'needs_review' ? 'open' : 'resolved';
    }

    private function applyProjectDecision(Project $project, string $action): void
    {
        $project->forceFill([
            'moderation_status' => $this->contentStatusFor($action),
        ])->save();
    }

    private function applyReleaseDecision(Release $release, string $action): void
    {
        $release->forceFill([
            'moderation_status' => $this->contentStatusFor($action),
        ])->save();
    }

    private function applyReportDecision(ContentReport $report, string $action): void
    {
        $report->forceFill([
            'status' => match ($action) {
                'approve' => 'actioned',
                'block' => 'dismissed',
                default => 'open',
            },
        ])->save();
    }

    private function applyRightsCaseDecision(RightsCase $case, string $action): void
    {
        $case->forceFill([
            'status' => match ($action) {
                'approve' => 'actioned',
                'block' => 'rejected',
                default => 'open',
            },
        ])->save();
    }

    private function applyExternalTargetDecision(ExternalTarget $target, string $action): void
    {
        $target->forceFill(match ($action) {
            'approve' => [
                'trust_status' => 'approved',
                'domain_status' => DomainStatus::Known,
            ],
            'block' => [
                'trust_status' => 'blocked',
                'domain_status' => DomainStatus::Blocked,
            ],
            default => [
                'trust_status' => 'needs_review',
                'domain_status' => DomainStatus::Suspicious,
            ],
        })->save();
    }

    private function contentStatusFor(string $action): string
    {
        return match ($action) {
            'approve' => 'approved',
            'block' => 'rejected',
            default => 'pending',
        };
    }

    private function statusSnapshot(?object $subject): array
    {
        return match (true) {
            $subject instanceof ContentReport => [
                'project_id' => $subject->project_id,
                'project_snapshot' => $subject->project_snapshot,
                'reason' => $subject->reason,
                'status' => $subject->status,
            ],
            $subject instanceof RightsCase => [
                'project_id' => $subject->project_id,
                'claim_type' => $subject->claim_type,
                'status' => $subject->status,
            ],
            $subject instanceof Project => [
                'publication_status' => $subject->publication_status,
                'moderation_status' => $subject->moderation_status,
            ],
            $subject instanceof Release => [
                'published_at' => $subject->published_at?->toISOString(),
                'moderation_status' => $subject->moderation_status,
            ],
            $subject instanceof ExternalTarget => [
                'target_domain' => $subject->target_domain,
                'domain_status' => $subject->domain_status->value,
                'reachability_status' => $subject->reachability_status,
                'trust_status' => $subject->trust_status,
            ],
            default => [],
        };
    }
}
