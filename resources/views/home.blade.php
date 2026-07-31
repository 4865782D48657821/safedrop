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
        <div class="grid">
            @foreach ($projects as $project)
                <article class="card">
                    <h3>{{ $project['title'] }}</h3>
                    <p>{{ $project['summary'] }}</p>
                    <div class="meta">
                        <span class="pill">{{ $project['game'] }}</span>
                        <span class="pill">{{ $project['type'] }}</span>
                        <span class="pill">{{ $project['trust_status'] }}</span>
                    </div>
                    <a class="button" href="{{ route('projects.show', $project['slug']) }}">View project</a>
                </article>
            @endforeach
        </div>
    </section>
@endsection
