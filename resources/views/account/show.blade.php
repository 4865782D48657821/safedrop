@extends('layouts.app', ['title' => 'Account | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Account</h1>
        <p class="lede">{{ $user->name }}</p>
        <div class="meta">
            <span class="pill">{{ str_replace('_', ' ', $user->role->value) }}</span>
            <span class="pill">{{ $user->age_group->value }}</span>
        </div>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Creator Access</h2>
            <p>{{ $user->canPublishProjects() ? 'Publishing tools are available.' : 'Publishing requires a creator role.' }}</p>
            @if ($user->canPublishProjects())
                <a class="button" href="{{ route('creator.dashboard') }}">Open creator tools</a>
            @endif
        </article>
        <article class="card">
            <h2>Monetization</h2>
            <p>{{ $user->canMonetizeProjects() ? 'Monetization is enabled.' : 'Monetization requires adult creator verification.' }}</p>
        </article>
    </section>

    <section>
        <h2>Saved Projects</h2>
        <div class="grid">
            @forelse ($savedProjects as $project)
                <article class="card">
                    <h3><a href="{{ route('projects.show', $project->slug) }}">{{ $project->title }}</a></h3>
                    <p>{{ $project->summary }}</p>
                    <div class="meta">
                        <span class="pill">{{ ucfirst($project->game) }}</span>
                        <span class="pill">{{ str_replace('_', ' ', $project->project_type) }}</span>
                        <span class="pill">By {{ $project->creator->name }}</span>
                    </div>
                    <form method="post" action="{{ route('projects.saved.destroy', $project->slug) }}">
                        @csrf
                        @method('delete')
                        <button class="button button-secondary" type="submit">Remove</button>
                    </form>
                </article>
            @empty
                <article class="card">
                    <h3>No saved projects yet</h3>
                    <p>Save reviewed projects from their project pages.</p>
                    <a class="button" href="{{ route('home') }}#projects">Browse projects</a>
                </article>
            @endforelse
        </div>
    </section>

    @if ($unavailableSavedProjects->isNotEmpty())
        <section>
            <h2>Unavailable Saved Projects</h2>
            <div class="grid">
                @foreach ($unavailableSavedProjects as $project)
                    <article class="card">
                        <h3>Unavailable project</h3>
                        <p>This saved project is no longer publicly available.</p>
                        <form method="post" action="{{ route('saved-projects.destroy', $project->pivot->id) }}">
                            @csrf
                            @method('delete')
                            <button class="button button-secondary" type="submit">Remove</button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
