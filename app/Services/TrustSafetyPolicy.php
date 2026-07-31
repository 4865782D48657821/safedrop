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
        return $target->publicDestinationUrl() !== null;
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
}
