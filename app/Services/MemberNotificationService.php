<?php

namespace App\Services;

use App\Models\MemberNotification;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;

class MemberNotificationService
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    public function notifyFollowersForProject(Project $project): int
    {
        $project->loadMissing(['creator.followerUsers', 'latestPublicRelease.publicExternalTargets']);
        $creator = $project->creator;

        if (! $creator instanceof User) {
            return 0;
        }

        $count = 0;

        foreach ($creator->followerUsers as $follower) {
            if (! $this->allows($follower, $creator, 'notify_new_projects')) {
                continue;
            }

            if (! $this->policy->canIncludeProjectInFeed($project, $follower)) {
                continue;
            }

            $notification = $this->createNotification($follower, [
                'creator_id' => $creator->id,
                'project_id' => $project->id,
                'event_type' => MemberNotification::NEW_PROJECT,
                'dedupe_key' => "project:{$project->id}",
                'title' => "New project from {$creator->name}",
                'body' => $project->title,
            ]);

            $count += $notification->wasRecentlyCreated ? 1 : 0;
        }

        return $count;
    }

    public function notifyFollowersForRelease(Release $release): int
    {
        $release->loadMissing(['project.creator.followerUsers', 'project.latestPublicRelease.publicExternalTargets']);
        $project = $release->project;
        $creator = $project?->creator;

        if (! $project instanceof Project || ! $creator instanceof User) {
            return 0;
        }

        if (! $this->policy->canExposeRelease($release)) {
            return 0;
        }

        $count = 0;

        foreach ($creator->followerUsers as $follower) {
            if (! $this->allows($follower, $creator, 'notify_new_releases')) {
                continue;
            }

            if (! $this->policy->canIncludeProjectInFeed($project, $follower)) {
                continue;
            }

            $notification = $this->createNotification($follower, [
                'creator_id' => $creator->id,
                'project_id' => $project->id,
                'release_id' => $release->id,
                'event_type' => MemberNotification::NEW_RELEASE,
                'dedupe_key' => "release:{$release->id}",
                'title' => "New release from {$creator->name}",
                'body' => "{$project->title} {$release->version}",
            ]);

            $count += $notification->wasRecentlyCreated ? 1 : 0;
        }

        return $count;
    }

    public function notifyFollowersForLiveSession(User $creator, string $sessionId, string $title): int
    {
        $creator->loadMissing('followerUsers');
        $count = 0;

        foreach ($creator->followerUsers as $follower) {
            if (! $this->allows($follower, $creator, 'notify_livestreams')) {
                continue;
            }

            $notification = $this->createNotification($follower, [
                'creator_id' => $creator->id,
                'event_type' => MemberNotification::LIVE_STREAM,
                'dedupe_key' => "live:{$creator->id}:{$sessionId}",
                'title' => "{$creator->name} is live",
                'body' => $title,
            ]);

            $count += $notification->wasRecentlyCreated ? 1 : 0;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createNotification(User $user, array $attributes): MemberNotification
    {
        return $user->memberNotifications()->createOrFirst([
            'event_type' => $attributes['event_type'],
            'dedupe_key' => $attributes['dedupe_key'],
        ], $attributes);
    }

    private function allows(User $user, User $creator, string $column): bool
    {
        $preference = $user->creatorNotificationPreferences()
            ->where('creator_id', $creator->id)
            ->first();

        return $preference?->{$column} ?? true;
    }
}
