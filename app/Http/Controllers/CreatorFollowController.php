<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreatorFollowController extends Controller
{
    public function store(Request $request, int $creator): RedirectResponse
    {
        abort_if($request->user()->id === $creator, 403);

        $creator = User::query()
            ->whereKey($creator)
            ->whereIn('role', [
                UserRole::JuniorCreator->value,
                UserRole::AdultCreatorUnverified->value,
                UserRole::AdultCreatorVerified->value,
            ])
            ->whereHas('projects', fn ($query) => $query->publiclyVisible())
            ->firstOrFail();

        $request->user()->followedCreators()->syncWithoutDetaching([$creator->id]);

        return back()->with('status', "You are following {$creator->name}.");
    }

    public function destroy(Request $request, int $creator): RedirectResponse
    {
        $request->user()->followedCreators()->detach($creator);

        return back()->with('status', 'Creator removed from following.');
    }

    public function destroyUnavailable(Request $request, int $creatorFollow): RedirectResponse
    {
        $request->user()
            ->followedCreators()
            ->wherePivot('id', $creatorFollow)
            ->detach();

        return back()->with('status', 'Unavailable creator removed from following.');
    }
}
