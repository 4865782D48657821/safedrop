<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRating;
use App\Services\TrustSafetyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectRatingController extends Controller
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    public function store(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->policy->canViewProject($project, $request->user()), 404);

        $data = $request->validate([
            'signal' => ['required', 'string', Rule::in(ProjectRating::SIGNALS)],
        ]);

        $request->user()->projectRatings()->updateOrCreate(
            ['project_id' => $project->id],
            ['signal' => $data['signal']],
        );

        return back()->with('status', 'Project feedback recorded.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->policy->canViewProject($project, $request->user()), 404);

        $request->user()->projectRatings()->where('project_id', $project->id)->delete();

        return back()->with('status', 'Project feedback removed.');
    }
}
