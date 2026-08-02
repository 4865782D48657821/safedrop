<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('account.show', [
            'user' => $request->user(),
            'savedProjects' => $request->user()
                ->savedProjects()
                ->publiclyVisible()
                ->with(['creator', 'latestPublicRelease.publicExternalTargets'])
                ->latest('saved_projects.created_at')
                ->get(),
            'unavailableSavedProjects' => $request->user()
                ->savedProjects()
                ->whereNot(function ($query): void {
                    $query
                        ->whereIn('publication_status', config('safedrop.public_project_statuses.publication'))
                        ->whereIn('moderation_status', config('safedrop.public_project_statuses.moderation'));
                })
                ->select('projects.id')
                ->withPivot('id', 'created_at')
                ->latest('saved_projects.created_at')
                ->get(),
            'followedCreators' => $request->user()
                ->followedCreators()
                ->whereHas('projects', fn ($query) => $query->publiclyVisible())
                ->withCount(['projects as public_projects_count' => fn ($query) => $query->publiclyVisible()])
                ->latest('creator_follows.created_at')
                ->get(['users.id', 'users.name']),
            'unavailableFollowedCreators' => $request->user()
                ->followedCreators()
                ->whereDoesntHave('projects', fn ($query) => $query->publiclyVisible())
                ->select('users.id')
                ->withPivot('id', 'created_at')
                ->latest('creator_follows.created_at')
                ->get(),
        ]);
    }
}
