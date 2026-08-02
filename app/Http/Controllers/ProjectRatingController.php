<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectRatingController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $project = Project::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

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

        $request->user()->projectRatings()->where('project_id', $project->id)->delete();

        return back()->with('status', 'Project feedback removed.');
    }
}
