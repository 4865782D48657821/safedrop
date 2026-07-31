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
@endsection
