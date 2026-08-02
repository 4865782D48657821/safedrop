<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TrustSafetyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private TrustSafetyPolicy $policy) {}

    public function edit(Request $request): View
    {
        return view('onboarding.edit', [
            'games' => config('safedrop.games'),
            'options' => config('safedrop.onboarding'),
            'knownCreators' => $this->knownCreators($request->user()),
            'preference' => $request->user()->onboardingPreference()->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $games = config('safedrop.games');
        $gameKeys = array_keys($games);
        $projectTypes = array_values(array_unique(array_merge(...array_column($games, 'project_types'))));
        $categories = config('safedrop.onboarding.categories');
        $versions = config('safedrop.onboarding.versions');
        $platforms = config('safedrop.onboarding.platforms');
        $knownCreatorIds = $this->knownCreators($request->user())->pluck('id')->all();

        $data = $request->validate([
            'games' => ['nullable', 'array'],
            'games.*' => ['string', Rule::in($gameKeys)],
            'project_types' => ['nullable', 'array'],
            'project_types.*' => ['string', Rule::in($projectTypes)],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', Rule::in($categories)],
            'versions' => ['nullable', 'array'],
            'versions.*' => ['string', Rule::in($versions)],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', Rule::in($platforms)],
            'creator_ids' => ['nullable', 'array'],
            'creator_ids.*' => ['integer', Rule::in($knownCreatorIds)],
        ]);

        $request->user()->onboardingPreference()->updateOrCreate(
            [],
            [
                'games' => $this->values($data, 'games'),
                'project_types' => $this->values($data, 'project_types'),
                'categories' => $this->values($data, 'categories'),
                'versions' => $this->values($data, 'versions'),
                'platforms' => $this->values($data, 'platforms'),
                'creator_ids' => array_map('intval', $this->values($data, 'creator_ids')),
                'completed_at' => now(),
                'skipped_at' => null,
            ],
        );

        return redirect()->route('home')->with('status', 'Interests updated.');
    }

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->onboardingPreference()->updateOrCreate(
            [],
            [
                'games' => [],
                'project_types' => [],
                'categories' => [],
                'versions' => [],
                'platforms' => [],
                'creator_ids' => [],
                'completed_at' => null,
                'skipped_at' => now(),
            ],
        );

        return redirect()->route('home')->with('status', 'Onboarding skipped.');
    }

    private function knownCreators(User $viewer)
    {
        return User::query()
            ->whereIn('role', [
                UserRole::JuniorCreator->value,
                UserRole::AdultCreatorUnverified->value,
                UserRole::AdultCreatorVerified->value,
            ])
            ->whereHas('projects', fn ($query) => $query->publiclyVisible())
            ->with(['projects.latestPublicRelease.publicExternalTargets'])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (User $creator): bool => $creator->projects->contains(
                fn ($project): bool => $this->policy->canIncludeProjectInFeed($project, $viewer),
            ))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function values(array $data, string $key): array
    {
        $values = $data[$key] ?? [];

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique($values));
    }
}
