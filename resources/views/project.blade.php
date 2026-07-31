@extends('layouts.app', ['title' => $project['title'].' | Safedrop'])

@section('content')
    <section class="hero">
        <h1>{{ $project['title'] }}</h1>
        <p class="lede">{{ $project['summary'] }}</p>
        <div class="meta">
            <span class="pill">{{ $project['game'] }}</span>
            <span class="pill">{{ $project['type'] }}</span>
            <span class="pill">By {{ $project['creator'] }}</span>
            <span class="pill">{{ $project['language'] }}</span>
        </div>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Safety Status</h2>
            <p>This destination is currently marked as <strong>{{ $project['trust_status'] }}</strong>.</p>
            <p>Target domain: <strong>{{ $targetHost }}</strong></p>
            <a class="button" href="{{ route('redirect.preview', $project['slug']) }}">Continue safely</a>
        </article>
        <article class="card">
            <h2>Tags</h2>
            <div class="meta">
                @foreach ($project['tags'] as $tag)
                    <span class="pill">{{ $tag }}</span>
                @endforeach
            </div>
        </article>
    </section>
@endsection
