@extends('layouts.app', ['title' => 'Safedrop'])

@section('content')
    <section class="hero">
        <h1>Safedrop</h1>
        <p class="lede">
            A safe discovery layer for Minecraft and Roblox projects, creator updates, and reviewed external destinations.
        </p>
    </section>

    <section class="grid" aria-label="Supported games">
        @foreach ($games as $game)
            <article class="card">
                <h2>{{ $game['label'] }}</h2>
                <p>Project types supported in the MVP data model:</p>
                <div class="meta">
                    @foreach ($game['project_types'] as $type)
                        <span class="pill">{{ str_replace('_', ' ', $type) }}</span>
                    @endforeach
                </div>
            </article>
        @endforeach
    </section>

    <section id="projects">
        <h2>Reviewed Projects</h2>
        @if ($showOnboardingPrompt)
            <article class="card">
                <h3>Personalize your first feed</h3>
                <p>Choose games, project types, categories, versions, platforms, and creators.</p>
                <div class="actions">
                    <a class="button" href="{{ route('onboarding.edit') }}">Set interests</a>
                    <form method="post" action="{{ route('onboarding.skip') }}">
                        @csrf
                        <button class="button button-secondary" type="submit">Skip</button>
                    </form>
                </div>
            </article>
        @endif
        <form class="filters" method="get" action="{{ route('home') }}#projects">
            <label>
                Search
                <input name="q" value="{{ $filters['q'] }}" maxlength="80">
            </label>

            <label>
                Game
                <select name="game">
                    <option value="">All games</option>
                    @foreach ($games as $key => $game)
                        <option value="{{ $key }}" @selected($filters['game'] === $key)>{{ $game['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Project type
                <select name="project_type">
                    <option value="">All types</option>
                    @foreach ($projectTypes as $type)
                        <option value="{{ $type }}" @selected($filters['project_type'] === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                    @endforeach
                </select>
            </label>

            <div class="actions">
                <button class="button" type="submit">Apply filters</button>
                <a href="{{ route('home') }}#projects">Clear</a>
            </div>
        </form>

        <div class="grid">
            @forelse ($projects as $project)
                <article class="card">
                    <h3>{{ $project->title }}</h3>
                    <p>{{ $project->summary }}</p>
                    <div class="meta">
                        <span class="pill">{{ ucfirst($project->game) }}</span>
                        <span class="pill">{{ str_replace('_', ' ', $project->project_type) }}</span>
                        <span class="pill">{{ $project->latestPublicRelease?->publicExternalTargets->first()?->effectiveDomainStatus() ?? 'needs review' }}</span>
                    </div>
                    <a class="button" href="{{ route('projects.show', $project->slug) }}">View project</a>
                </article>
            @empty
                <article class="card">
                    <h3>No reviewed projects found</h3>
                    <p>Try a broader game, type, or search filter.</p>
                </article>
            @endforelse
        </div>
    </section>
@endsection
