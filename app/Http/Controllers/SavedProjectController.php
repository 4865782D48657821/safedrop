<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedProjectController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        $request->user()->savedProjects()->syncWithoutDetaching([$project->id]);

        return back()->with('status', 'Project saved.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

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
