<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInterestFeedback;
use App\Services\TrustSafetyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectInterestFeedbackController extends Controller
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    public function store(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->policy->canViewProject($project, $request->user()), 404);

        $request->user()->projectInterestFeedback()->updateOrCreate(
            ['project_id' => $project->id],
            ['signal' => ProjectInterestFeedback::NOT_INTERESTED],
        );

        return back()->with('status', 'Project marked as not interesting for your feed.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->policy->canViewProject($project, $request->user()), 404);

        $request->user()->projectInterestFeedback()->where('project_id', $project->id)->delete();

        return back()->with('status', 'Project interest feedback removed.');
    }

    public function destroyUnavailable(Request $request, int $projectInterestFeedback): RedirectResponse
    {
        $request->user()
            ->projectInterestFeedback()
            ->whereKey($projectInterestFeedback)
            ->delete();

        return back()->with('status', 'Unavailable project interest feedback removed.');
    }
}
