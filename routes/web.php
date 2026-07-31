<?php

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
