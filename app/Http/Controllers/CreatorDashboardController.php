<?php

namespace App\Http\Controllers;

use App\Models\ExternalTarget;
use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\Release;
use App\Services\UrlReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreatorDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->canPublishProjects(), 403);

        return view('creator.dashboard', [
            'user' => $request->user(),
            'projects' => $request->user()
                ->projects()
                ->with('releases.externalTargets')
                ->latest()
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->canPublishProjects(), 403);

        return view('creator.projects.create', [
            'games' => config('safedrop.games'),
        ]);
    }

    public function store(Request $request, UrlReviewService $urlReviewService): RedirectResponse
    {
        abort_unless($request->user()?->canPublishProjects(), 403);

        $games = config('safedrop.games');
        $gameKeys = array_keys($games);
        $selectedGame = $request->input('game');
        $projectTypes = is_string($selectedGame)
            ? ($games[$selectedGame]['project_types'] ?? [])
            : [];

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'game' => ['required', 'string', Rule::in($gameKeys)],
            'project_type' => ['required', 'string', Rule::in($projectTypes)],
            'tags' => ['nullable', 'string', 'max:240'],
            'version' => ['required', 'string', 'max:60'],
            'external_url' => ['required', 'string', 'max:2048'],
        ]);

        $review = $urlReviewService->review($validated['external_url']);

        if ($review->isBlocked()) {
            throw ValidationException::withMessages([
                'external_url' => 'This external destination cannot be accepted for review.',
            ]);
        }

        $project = DB::transaction(function () use ($request, $validated, $review): Project {
            $project = Project::query()->create([
                'creator_id' => $request->user()->id,
                'slug' => $this->uniqueSlug($validated['title']),
                'title' => $validated['title'],
                'summary' => $validated['summary'],
                'description' => $validated['description'] ?? null,
                'game' => $validated['game'],
                'project_type' => $validated['project_type'],
                'tags' => $this->tagsFromInput($validated['tags'] ?? null),
                'language' => 'en',
                'publication_status' => 'published',
                'moderation_status' => 'pending',
                'age_rating' => '12+',
            ]);

            $release = Release::query()->create([
                'project_id' => $project->id,
                'version' => $validated['version'],
                'published_at' => now(),
                'moderation_status' => 'pending',
            ]);

            $target = $release->externalTargets()->save(ExternalTarget::makeFromReview($release, $review));

            ModerationCase::openForSubject(
                $project,
                'project_metadata',
                'medium',
                'Creator submitted a project for publication review.',
            );
            ModerationCase::openForSubject(
                $release,
                'release',
                'medium',
                'Creator submitted an initial release for review.',
            );
            ModerationCase::openForSubject(
                $target,
                'external_target',
                $review->signals === [] ? 'medium' : 'high',
                $review->targetDomain === null
                    ? 'Creator submitted an external target for review.'
                    : "Creator submitted external target domain {$review->targetDomain} for review.",
            );

            return $project;
        });

        return redirect()
            ->route('creator.dashboard')
            ->with('status', "Project {$project->title} was submitted for moderation.");
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'project';
        }

        $slug = $base;
        $suffix = 2;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return list<string>
     */
    private function tagsFromInput(?string $tags): array
    {
        if ($tags === null || trim($tags) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                fn (string $tag): string => Str::lower(Str::limit(trim($tag), 32, '')),
                explode(',', $tags),
            ),
            fn (string $tag): bool => $tag !== '',
        )));
    }
}
