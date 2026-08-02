<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function update(Request $request, int $creator): RedirectResponse
    {
        $creator = $request->user()
            ->followedCreators()
            ->whereKey($creator)
            ->firstOrFail();

        $data = $request->validate([
            'notify_new_projects' => ['required', 'boolean'],
            'notify_new_releases' => ['required', 'boolean'],
            'notify_livestreams' => ['required', 'boolean'],
        ]);

        $request->user()->creatorNotificationPreferences()->updateOrCreate(
            ['creator_id' => $creator->id],
            $data,
        );

        return back()->with('status', 'Notification preferences updated.');
    }
}
