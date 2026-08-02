<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\TrustSafetyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedProjectController extends Controller
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    public function store(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->policy->canViewProject($project, $request->user()), 404);

        $request->user()->savedProjects()->syncWithoutDetaching([$project->id]);

        return back()->with('status', 'Project saved.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->policy->canViewProject($project, $request->user()), 404);

        $request->user()->savedProjects()->detach($project->id);

        return back()->with('status', 'Project removed from saved projects.');
    }

    public function destroyUnavailable(Request $request, int $savedProject): RedirectResponse
    {
        $request->user()
            ->savedProjects()
            ->wherePivot('id', $savedProject)
            ->detach();

        return back()->with('status', 'Unavailable project removed from saved projects.');
    }
}
