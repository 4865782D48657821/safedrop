<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CreatorDashboardController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\RightsCaseController;
use App\Models\Project;
use App\Services\TrustSafetyPolicy;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::get('/', function () {
    $games = config('safedrop.games');
    $projects = Project::query()
        ->publiclyVisible()
        ->with(['creator', 'latestPublicRelease.publicExternalTargets'])
        ->latest('updated_at')
        ->get();

    return view('home', [
        'games' => $games,
        'projects' => $projects,
    ]);
})->name('home');

Route::get('/projects/{slug}', function (string $slug) {
    $project = Project::query()
        ->publiclyVisible()
        ->with(['creator', 'latestPublicRelease.publicExternalTargets'])
        ->where('slug', $slug)
        ->firstOrFail();

    $policy = app(TrustSafetyPolicy::class);
    $target = $project->latestPublicRelease?->publicExternalTargets
        ->first(fn ($target): bool => $policy->canRedirectToTarget($target));

    return view('project', [
        'project' => $project,
        'target' => $target,
        'adsAllowed' => $policy->canShowRevenueAdsOnProject($project),
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
    Route::get('/creator', CreatorDashboardController::class)->name('creator.dashboard');
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/cases/{case}/decisions', [ModerationController::class, 'decide'])->name('moderation.decide');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/creator/projects/create', [CreatorDashboardController::class, 'create'])->name('creator.projects.create');
    Route::post('/creator/projects', [CreatorDashboardController::class, 'store'])->name('creator.projects.store');
});
