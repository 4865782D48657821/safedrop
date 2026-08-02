<?php

namespace App\Services;

use App\Models\ExternalTarget;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;

class TrustSafetyPolicy
{
    public function canDiscoverProject(Project $project): bool
    {
        return in_array($project->publication_status, config('safedrop.public_project_statuses.publication'), true)
            && in_array($project->moderation_status, config('safedrop.public_project_statuses.moderation'), true);
    }

    public function canExposeRelease(Release $release): bool
    {
        return $release->published_at !== null
            && in_array($release->moderation_status, config('safedrop.public_release_statuses.moderation'), true);
    }

    public function canRedirectToTarget(ExternalTarget $target): bool
    {
        return $this->redirectDestinationForTarget($target) !== null;
    }

    public function canListTargetInFeed(ExternalTarget $target): bool
    {
        return $this->canRedirectToTarget($target);
    }

    public function canIncludeProjectInFeed(Project $project, ?User $viewer = null): bool
    {
        if (! $this->canDiscoverProject($project)) {
            return false;
        }

        if (! $this->canViewProject($project, $viewer)) {
            return false;
        }

        $release = $project->latestPublicRelease;

        if (! $release instanceof Release || ! $this->canExposeRelease($release)) {
            return false;
        }

        return $release->publicExternalTargets->contains(
            fn (ExternalTarget $target): bool => $this->canListTargetInFeed($target),
        );
    }

    public function canViewProject(Project $project, ?User $viewer = null): bool
    {
        if (! $this->canDiscoverProject($project)) {
            return false;
        }

        return $this->canShowProjectToViewerAgeGroup($project, $viewer);
    }

    public function redirectDestinationForTarget(ExternalTarget $target): ?string
    {
        return $target->publicDestinationUrl();
    }

    public function canMonetize(User $user): bool
    {
        return $user->canMonetizeProjects();
    }

    public function canShowRevenueAdsOnProject(Project $project): bool
    {
        $creator = $project->creator;

        if (! $creator instanceof User) {
            return false;
        }

        return $this->canDiscoverProject($project)
            && $this->canMonetize($creator)
            && ! in_array($project->age_rating, config('safedrop.ad_free_age_ratings'), true);
    }

    private function canShowProjectToViewerAgeGroup(Project $project, ?User $viewer): bool
    {
        if ($viewer instanceof User && $viewer->age_group->isAdult()) {
            return true;
        }

        if ($project->age_rating === null) {
            return true;
        }

        return in_array($project->age_rating, config('safedrop.junior_feed_age_ratings'), true);
    }
}
