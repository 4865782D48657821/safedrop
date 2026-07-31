@extends('layouts.app', ['title' => 'Creator Tools | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Creator Tools</h1>
        <p class="lede">Prepare project metadata, releases, and reviewed external destinations.</p>
        <p><a class="button" href="{{ route('creator.projects.create') }}">New project</a></p>
    </section>

    @if (session('status'))
        <p class="notice">{{ session('status') }}</p>
    @endif

    <section class="grid">
        <article class="card">
            <h2>Publishing</h2>
            <p>Project publishing is available for {{ str_replace('_', ' ', $user->role->value) }} accounts.</p>
        </article>
        <article class="card">
            <h2>Revenue Safety</h2>
            <p>{{ $user->canShowRevenueAdsOnProjectPages() ? 'Revenue ads may be shown after review.' : 'Revenue ads are disabled for this account.' }}</p>
        </article>
    </section>

    <section>
        <h2>Your Projects</h2>

        @if ($projects->isEmpty())
            <p class="lede">No projects submitted yet.</p>
        @else
            <section class="grid">
                @foreach ($projects as $project)
                    <article class="card">
                        <h3>{{ $project->title }}</h3>
                        <p>{{ $project->summary }}</p>
                        <div class="meta">
                            <span class="pill">{{ ucfirst($project->game) }}</span>
                            <span class="pill">{{ str_replace('_', ' ', $project->project_type) }}</span>
                            <span class="pill">{{ $project->publication_status }}</span>
                            <span class="pill">{{ $project->moderation_status }}</span>
                        </div>
                        <p>{{ $project->releases->count() }} release submitted.</p>
                    </article>
                @endforeach
            </section>
        @endif
    </section>
@endsection
