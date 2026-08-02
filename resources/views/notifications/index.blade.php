@extends('layouts.app', ['title' => 'Notifications | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Notifications</h1>
        <p class="lede">Updates from creators you follow.</p>
    </section>

    <section>
        <div class="grid">
            @forelse ($notifications as $notification)
                <article class="card">
                    <h2>{{ $notification->title }}</h2>
                    @if ($notification->body)
                        <p>{{ $notification->body }}</p>
                    @endif
                    <div class="meta">
                        <span class="pill">{{ str_replace('_', ' ', $notification->event_type) }}</span>
                        <span class="pill">{{ $notification->created_at->diffForHumans() }}</span>
                        <span class="pill">{{ $notification->read_at ? 'Read' : 'Unread' }}</span>
                    </div>
                    <div class="actions">
                        @if ($notification->project)
                            <a class="button" href="{{ route('projects.show', $notification->project->slug) }}">View project</a>
                        @endif
                        @unless ($notification->read_at)
                            <form method="post" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button class="button button-secondary" type="submit">Mark read</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @empty
                <article class="card">
                    <h2>No notifications yet</h2>
                    <p>Follow creators and enable updates to receive project, release, and live notifications.</p>
                </article>
            @endforelse
        </div>
    </section>
@endsection
