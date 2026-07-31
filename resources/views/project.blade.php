@extends('layouts.app', ['title' => $project->title.' | Safedrop'])

@section('content')
    <section class="hero">
        <h1>{{ $project->title }}</h1>
        <p class="lede">{{ $project->summary }}</p>
        <div class="meta">
            <span class="pill">{{ ucfirst($project->game) }}</span>
            <span class="pill">{{ str_replace('_', ' ', $project->project_type) }}</span>
            <span class="pill">By {{ $project->creator->name }}</span>
            <span class="pill">{{ $project->language }}</span>
        </div>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Safety Status</h2>
            @if ($target)
                <p>This destination is currently marked as <strong>{{ $target->trust_status }}</strong>.</p>
                <p>Domain status: <strong>{{ $target->effectiveDomainStatus() }}</strong></p>
                <p>Target domain: <strong>{{ $target->target_domain }}</strong></p>
                <a class="button" href="{{ route('redirect.preview', $project->slug) }}">Continue safely</a>
            @else
                <p>This project does not have an approved external destination yet.</p>
            @endif
        </article>
        <article class="card">
            <h2>Tags</h2>
            <div class="meta">
                @foreach ($project->tags ?? [] as $tag)
                    <span class="pill">{{ $tag }}</span>
                @endforeach
            </div>
        </article>
    </section>
@endsection
