<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CreatorDashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    $games = config('safedrop.games');
    $projects = config('safedrop.seed_projects');

    return view('home', [
        'games' => $games,
        'projects' => $projects,
    ]);
})->name('home');

Route::get('/projects/{slug}', function (string $slug) {
    $project = collect(config('safedrop.seed_projects'))->firstWhere('slug', $slug);

    abort_unless($project, 404);

    return view('project', [
        'project' => $project,
        'targetHost' => parse_url($project['external_url'], PHP_URL_HOST),
    ]);
})->name('projects.show');

Route::get('/go/{slug}', function (string $slug) {
    $project = collect(config('safedrop.seed_projects'))->firstWhere('slug', $slug);

    abort_unless($project, 404);
    abort_if($project['trust_status'] !== 'approved', 403);

    return view('redirect', [
        'project' => $project,
        'targetHost' => Str::of(parse_url($project['external_url'], PHP_URL_HOST))->lower(),
    ]);
})->name('redirect.preview');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/account', AccountController::class)->name('account.show');
    Route::get('/creator', CreatorDashboardController::class)->name('creator.dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
