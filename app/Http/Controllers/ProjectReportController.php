<?php

namespace App\Http\Controllers;

use App\Models\ContentReport;
use App\Models\ModerationCase;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectReportController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $request->validate([
            'reason' => ['required', Rule::in(config('safedrop.report_reasons'))],
            'details' => ['required', 'string', 'min:10', 'max:2000'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
        ]);

        $reporterEmail = strtolower((string) ($request->user()?->email ?? ($data['reporter_email'] ?? '')));
        $report = ContentReport::query()->firstOrCreate(
            [
                'fingerprint' => hash('sha256', implode('|', [
                    $project->id,
                    $data['reason'],
                    $reporterEmail,
                    trim($data['details']),
                ])),
            ],
            [
                'project_id' => $project->id,
                'reporter_id' => $request->user()?->id,
                'reporter_email' => $reporterEmail !== '' ? $reporterEmail : null,
                'project_snapshot' => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->title,
                ],
                'reason' => $data['reason'],
                'details' => $data['details'],
            ],
        );

        ModerationCase::openForSubject(
            $report,
            'report',
            $data['reason'] === 'unsafe_link' ? 'high' : 'medium',
            "Report for {$project->title}: {$data['reason']}",
        );

        return redirect()
            ->route('projects.show', $project->slug)
            ->with('status', 'Report submitted for moderation.');
    }
}
