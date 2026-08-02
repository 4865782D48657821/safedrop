<?php

namespace App\Http\Controllers;

use App\Models\MemberNotification;
use App\Services\TrustSafetyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->memberNotifications()
            ->with(['creator', 'project.latestPublicRelease.publicExternalTargets', 'release'])
            ->latest()
            ->get()
            ->filter(fn (MemberNotification $notification): bool => $this->canShow($notification, $request))
            ->values();

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, int $notification): RedirectResponse
    {
        $request->user()
            ->memberNotifications()
            ->whereKey($notification)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'Notification marked as read.');
    }

    private function canShow(MemberNotification $notification, Request $request): bool
    {
        if (! $notification->project) {
            return true;
        }

        if ($notification->event_type === MemberNotification::NEW_RELEASE) {
            return $notification->release
                && $this->policy->canExposeRelease($notification->release)
                && $this->policy->canIncludeProjectInFeed($notification->project, $request->user());
        }

        return $this->policy->canIncludeProjectInFeed($notification->project, $request->user());
    }
}
