<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CreatorDashboardController;
use App\Http\Controllers\CreatorFollowController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\ProjectInterestFeedbackController;
use App\Http\Controllers\ProjectRatingController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\RightsCaseController;
use App\Http\Controllers\SavedProjectController;
use App\Models\Project;
use App\Services\ProjectFeed;
use App\Services\TrustSafetyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::get('/', function (Request $request) {
    $games = config('safedrop.games');
    $game = $request->query('game');
    $selectedGame = is_string($game) && array_key_exists($game, $games) ? $game : null;

    $projectTypes = $selectedGame
        ? $games[$selectedGame]['project_types']
        : array_values(array_unique(array_merge(...array_column($games, 'project_types'))));

    $projectType = $request->query('project_type');
    $selectedProjectType = is_string($projectType) && in_array($projectType, $projectTypes, true) ? $projectType : null;

    $q = $request->query('q');
    $search = is_string($q) ? trim(substr($q, 0, 80)) : '';

    return view('home', [
        'games' => $games,
        'projects' => app(ProjectFeed::class)->projects([
            'game' => $selectedGame,
            'project_type' => $selectedProjectType,
            'q' => $search,
        ], auth()->user()),
        'filters' => [
            'game' => $selectedGame,
            'project_type' => $selectedProjectType,
            'q' => $search,
        ],
        'projectTypes' => $projectTypes,
    ]);
})->name('home');

Route::get('/projects/{slug}', function (string $slug) {
    $project = Project::query()
        ->publiclyVisible()
        ->with([
            'creator' => fn ($query) => $query->withCount('followerUsers'),
            'latestPublicRelease.publicExternalTargets',
        ])
        ->withCount([
            'ratings as helpful_ratings_count' => fn ($query) => $query->where('signal', 'helpful'),
            'ratings as not_helpful_ratings_count' => fn ($query) => $query->where('signal', 'not_helpful'),
        ])
        ->where('slug', $slug)
        ->firstOrFail();

    $policy = app(TrustSafetyPolicy::class);
    abort_unless($policy->canViewProject($project, auth()->user()), 404);

    $target = $project->latestPublicRelease?->publicExternalTargets
        ->first(fn ($target): bool => $policy->canRedirectToTarget($target));

    return view('project', [
        'project' => $project,
        'target' => $target,
        'adsAllowed' => $policy->canShowRevenueAdsOnProject($project),
        'isSaved' => auth()->user()?->savedProjects()->whereKey($project->id)->exists() ?? false,
        'isFollowingCreator' => auth()->user()?->followedCreators()->whereKey($project->creator_id)->exists() ?? false,
        'currentRating' => auth()->user()?->projectRatings()->where('project_id', $project->id)->value('signal'),
        'isNotInterested' => auth()->user()?->projectInterestFeedback()->where('project_id', $project->id)->exists() ?? false,
    ]);
})->name('projects.show');

Route::post('/projects/{slug}/reports', [ProjectReportController::class, 'store'])
    ->middleware('throttle:reports')
    ->name('projects.reports.store');

Route::get('/rights', [RightsCaseController::class, 'create'])->name('rights.create');
Route::post('/rights', [RightsCaseController::class, 'store'])
    ->middleware('throttle:reports')
    ->name('rights.store');

Route::get('/go/{slug}', function (string $slug) {
    $project = Project::query()
        ->publiclyVisible()
        ->with(['latestPublicRelease.publicExternalTargets'])
        ->where('slug', $slug)
        ->firstOrFail();

    $policy = app(TrustSafetyPolicy::class);
    abort_unless($policy->canViewProject($project, auth()->user()), 404);

    $target = $project->latestPublicRelease?->publicExternalTargets
        ->first(fn ($target): bool => $policy->canRedirectToTarget($target));

    abort_unless($target, 403);

    return view('redirect', [
        'project' => $project,
        'target' => $target,
        'signedRedirectUrl' => URL::temporarySignedRoute(
            'redirect.out',
            now()->addMinutes((int) config('safedrop.redirects.signed_url_ttl_minutes')),
            ['slug' => $project->slug, 'target' => $target->id],
        ),
    ]);
})->middleware('throttle:redirect-previews')->name('redirect.preview');

Route::get('/go/{slug}/out/{target}', function (string $slug, int $target) {
    $project = Project::query()
        ->publiclyVisible()
        ->with(['latestPublicRelease.publicExternalTargets'])
        ->where('slug', $slug)
        ->firstOrFail();

    $policy = app(TrustSafetyPolicy::class);
    abort_unless($policy->canViewProject($project, auth()->user()), 404);

    $destinationUrl = null;
    $externalTarget = $project->latestPublicRelease?->publicExternalTargets
        ->first(function ($externalTarget) use ($target, $policy, &$destinationUrl): bool {
            if ($externalTarget->id !== $target) {
                return false;
            }

            $destinationUrl = $policy->redirectDestinationForTarget($externalTarget);

            return $destinationUrl !== null;
        });

    abort_unless($externalTarget && $destinationUrl !== null, 403);

    return redirect()->away($destinationUrl, 302, [
        'Referrer-Policy' => 'no-referrer',
    ]);
})->middleware(['signed', 'throttle:redirect-outbound'])->name('redirect.out');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/account', AccountController::class)->name('account.show');
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationCenterController::class, 'markRead'])->name('notifications.read');
    Route::put('/creators/{creator}/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('creator-notification-preferences.update');
    Route::get('/creator', CreatorDashboardController::class)->name('creator.dashboard');
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/cases/{case}/decisions', [ModerationController::class, 'decide'])->name('moderation.decide');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/projects/{slug}/saved', [SavedProjectController::class, 'store'])->name('projects.saved.store');
    Route::delete('/projects/{slug}/saved', [SavedProjectController::class, 'destroy'])->name('projects.saved.destroy');
    Route::delete('/saved-projects/{savedProject}', [SavedProjectController::class, 'destroyUnavailable'])->name('saved-projects.destroy');
    Route::post('/creators/{creator}/follow', [CreatorFollowController::class, 'store'])->name('creator-follows.store');
    Route::delete('/creators/{creator}/follow', [CreatorFollowController::class, 'destroy'])->name('creator-follows.destroy');
    Route::delete('/creator-follows/{creatorFollow}', [CreatorFollowController::class, 'destroyUnavailable'])->name('creator-follows.unavailable.destroy');
    Route::post('/projects/{slug}/rating', [ProjectRatingController::class, 'store'])
        ->middleware('throttle:project-ratings')
        ->name('projects.rating.store');
    Route::delete('/projects/{slug}/rating', [ProjectRatingController::class, 'destroy'])
        ->middleware('throttle:project-ratings')
        ->name('projects.rating.destroy');
    Route::post('/projects/{slug}/interest-feedback', [ProjectInterestFeedbackController::class, 'store'])
        ->middleware('throttle:project-interest-feedback')
        ->name('projects.interest-feedback.store');
    Route::delete('/projects/{slug}/interest-feedback', [ProjectInterestFeedbackController::class, 'destroy'])
        ->middleware('throttle:project-interest-feedback')
        ->name('projects.interest-feedback.destroy');
    Route::delete('/interest-feedback/{projectInterestFeedback}', [ProjectInterestFeedbackController::class, 'destroyUnavailable'])
        ->middleware('throttle:project-interest-feedback')
        ->name('interest-feedback.destroy');

    Route::get('/creator/projects/create', [CreatorDashboardController::class, 'create'])->name('creator.projects.create');
    Route::post('/creator/projects', [CreatorDashboardController::class, 'store'])->name('creator.projects.store');
});
