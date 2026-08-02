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
        <h2>Following</h2>
        <div class="grid">
            @forelse ($followedCreators as $creator)
                <article class="card">
                    <h3>{{ $creator->name }}</h3>
                    <p>{{ $creator->public_projects_count }} public {{ $creator->public_projects_count === 1 ? 'project' : 'projects' }}</p>
                    @php($preference = $notificationPreferences->get($creator->id))
                    <form method="post" action="{{ route('creator-notification-preferences.update', $creator->id) }}">
                        @csrf
                        @method('put')
                        <input type="hidden" name="notify_new_projects" value="0">
                        <input type="hidden" name="notify_new_releases" value="0">
                        <input type="hidden" name="notify_livestreams" value="0">
                        <label>
                            <input type="checkbox" name="notify_new_projects" value="1" @checked($preference?->notify_new_projects ?? true)>
                            New projects
                        </label>
                        <label>
                            <input type="checkbox" name="notify_new_releases" value="1" @checked($preference?->notify_new_releases ?? true)>
                            New releases
                        </label>
                        <label>
                            <input type="checkbox" name="notify_livestreams" value="1" @checked($preference?->notify_livestreams ?? true)>
                            Livestreams
                        </label>
                        <button class="button button-secondary" type="submit">Save notification preferences</button>
                    </form>
                    <form method="post" action="{{ route('creator-follows.destroy', $creator->id) }}">
                        @csrf
                        @method('delete')
                        <button class="button button-secondary" type="submit">Unfollow</button>
                    </form>
                </article>
            @empty
                <article class="card">
                    <h3>No followed creators yet</h3>
                    <p>Follow creators from reviewed project pages.</p>
                    <a class="button" href="{{ route('home') }}#projects">Browse projects</a>
                </article>
            @endforelse
        </div>
    </section>

    @if ($unavailableFollowedCreators->isNotEmpty())
        <section>
            <h2>Unavailable Followed Creators</h2>
            <div class="grid">
                @foreach ($unavailableFollowedCreators as $creator)
                    <article class="card">
                        <h3>Unavailable creator</h3>
                        <p>This followed creator no longer has public projects.</p>
                        <form method="post" action="{{ route('creator-follows.unavailable.destroy', $creator->pivot->id) }}">
                            @csrf
                            @method('delete')
                            <button class="button button-secondary" type="submit">Remove</button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

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

    @if ($unavailableInterestFeedback->isNotEmpty())
        <section>
            <h2>Unavailable Feed Preferences</h2>
            <div class="grid">
                @foreach ($unavailableInterestFeedback as $feedback)
                    <article class="card">
                        <h3>Unavailable project</h3>
                        <p>This feed preference points to a project that is no longer publicly available.</p>
                        <form method="post" action="{{ route('interest-feedback.destroy', $feedback->id) }}">
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
