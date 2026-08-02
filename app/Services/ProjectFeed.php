<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectInterestFeedback;
use App\Models\ProjectRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProjectFeed
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    /**
     * @param  array{game?: ?string, project_type?: ?string, q?: ?string}  $filters
     * @return Collection<int, Project>
     */
    public function projects(array $filters = [], ?User $viewer = null): Collection
    {
        $query = Project::query()
            ->publiclyVisible()
            ->whereHas('releases', fn (Builder $query) => $query
                ->publiclyExposable()
                ->whereHas('publicExternalTargets'))
            ->with(['creator', 'latestPublicRelease.publicExternalTargets'])
            ->withCount([
                'ratings as helpful_ratings_count' => fn (Builder $query) => $query->where('signal', ProjectRating::HELPFUL),
                'ratings as not_helpful_ratings_count' => fn (Builder $query) => $query->where('signal', ProjectRating::NOT_HELPFUL),
                'savedByUsers as saves_count',
            ]);

        $this->applyFilters($query, $filters);
        $this->applyViewerFilters($query, $viewer);

        $savedProjectIds = $viewer instanceof User
            ? $viewer->savedProjects()->pluck('projects.id')->all()
            : [];
        $followedCreatorIds = $viewer instanceof User
            ? $viewer->followedCreators()->pluck('users.id')->all()
            : [];
        $notHelpfulProjectIds = $viewer instanceof User
            ? $viewer->projectRatings()->where('signal', ProjectRating::NOT_HELPFUL)->pluck('project_id')->all()
            : [];

        return $query
            ->get()
            ->filter(fn (Project $project): bool => $this->policy->canIncludeProjectInFeed($project, $viewer))
            ->sort(fn (Project $left, Project $right): int => $this->compareProjects(
                $left,
                $right,
                $savedProjectIds,
                $followedCreatorIds,
                $notHelpfulProjectIds,
            ))
            ->values();
    }

    /**
     * @param  array{game?: ?string, project_type?: ?string, q?: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (($filters['game'] ?? null) !== null) {
            $query->where('game', $filters['game']);
        }

        if (($filters['project_type'] ?? null) !== null) {
            $query->where('project_type', $filters['project_type']);
        }

        $search = $filters['q'] ?? '';

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '\\%_').'%';

                $query
                    ->whereRaw("title like ? escape '\\'", [$like])
                    ->orWhereRaw("summary like ? escape '\\'", [$like])
                    ->orWhereRaw("tags like ? escape '\\'", [$like]);
            });
        }
    }

    private function applyViewerFilters(Builder $query, ?User $viewer): void
    {
        if (! $viewer instanceof User) {
            return;
        }

        $query->whereDoesntHave(
            'interestFeedback',
            fn (Builder $query) => $query
                ->where('user_id', $viewer->id)
                ->where('signal', ProjectInterestFeedback::NOT_INTERESTED),
        );
    }

    /**
     * @param  list<int>  $savedProjectIds
     * @param  list<int>  $followedCreatorIds
     * @param  list<int>  $notHelpfulProjectIds
     */
    private function compareProjects(
        Project $left,
        Project $right,
        array $savedProjectIds,
        array $followedCreatorIds,
        array $notHelpfulProjectIds,
    ): int {
        $scoreComparison = $this->score($right, $savedProjectIds, $followedCreatorIds, $notHelpfulProjectIds)
            <=> $this->score($left, $savedProjectIds, $followedCreatorIds, $notHelpfulProjectIds);

        if ($scoreComparison !== 0) {
            return $scoreComparison;
        }

        $rightTimestamp = $right->latestPublicRelease?->published_at?->getTimestamp() ?? $right->updated_at?->getTimestamp() ?? 0;
        $leftTimestamp = $left->latestPublicRelease?->published_at?->getTimestamp() ?? $left->updated_at?->getTimestamp() ?? 0;
        $recencyComparison = $rightTimestamp <=> $leftTimestamp;

        if ($recencyComparison !== 0) {
            return $recencyComparison;
        }

        return strcmp($left->title, $right->title);
    }

    /**
     * @param  list<int>  $savedProjectIds
     * @param  list<int>  $followedCreatorIds
     * @param  list<int>  $notHelpfulProjectIds
     */
    private function score(Project $project, array $savedProjectIds, array $followedCreatorIds, array $notHelpfulProjectIds): int
    {
        $score = ((int) $project->helpful_ratings_count * 4)
            - ((int) $project->not_helpful_ratings_count * 2)
            + ((int) $project->saves_count * 3);

        if (in_array($project->id, $savedProjectIds, true)) {
            $score += 12;
        }

        if (in_array($project->creator_id, $followedCreatorIds, true)) {
            $score += 10;
        }

        if (in_array($project->id, $notHelpfulProjectIds, true)) {
            $score -= 8;
        }

        return $score;
    }
}
