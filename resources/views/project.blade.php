@extends('layouts.app', ['title' => $project->title.' | Safedrop'])

@section('content')
    @if (session('status'))
        <section class="notice">{{ session('status') }}</section>
    @endif

    <section class="hero">
        <h1>{{ $project->title }}</h1>
        <p class="lede">{{ $project->summary }}</p>
        <div class="meta">
            <span class="pill">{{ ucfirst($project->game) }}</span>
            <span class="pill">{{ str_replace('_', ' ', $project->project_type) }}</span>
            <span class="pill">By {{ $project->creator->name }}</span>
            <span class="pill">{{ $project->language }}</span>
        </div>
        @auth
            <div class="actions">
                @if ($isSaved)
                    <form method="post" action="{{ route('projects.saved.destroy', $project->slug) }}">
                        @csrf
                        @method('delete')
                        <button class="button button-secondary" type="submit">Saved</button>
                    </form>
                @else
                    <form method="post" action="{{ route('projects.saved.store', $project->slug) }}">
                        @csrf
                        <button class="button" type="submit">Save project</button>
                    </form>
                @endif

                @if (auth()->id() !== $project->creator_id)
                    @if ($isFollowingCreator)
                        <form method="post" action="{{ route('creator-follows.destroy', $project->creator_id) }}">
                            @csrf
                            @method('delete')
                            <button class="button button-secondary" type="submit">Following creator</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('creator-follows.store', $project->creator_id) }}">
                            @csrf
                            <button class="button" type="submit">Follow creator</button>
                        </form>
                    @endif
                @endif
            </div>
        @else
            <div class="actions">
                <a class="button" href="{{ route('login') }}">Log in to save</a>
                <a class="button button-secondary" href="{{ route('login') }}">Log in to follow</a>
            </div>
        @endauth
    </section>

    <section class="grid">
        <article class="card">
            <h2>Creator</h2>
            <p>{{ $project->creator->name }}</p>
            @if ($project->creator->follower_users_count === 1)
                <p>1 follower</p>
            @else
                <p>{{ $project->creator->follower_users_count }} followers</p>
            @endif
        </article>
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
            <h2>Revenue Safety</h2>
            <p>{{ $adsAllowed ? 'Revenue ads are eligible after campaign review.' : 'Revenue ads are disabled for this project.' }}</p>
        </article>
        <article class="card">
            <h2>Project Feedback</h2>
            <p>{{ $project->helpful_ratings_count }} helpful · {{ $project->not_helpful_ratings_count }} not helpful</p>
            @auth
                <form class="actions" method="post" action="{{ route('projects.rating.store', $project->slug) }}">
                    @csrf
                    <button class="button {{ $currentRating === 'helpful' ? 'button-secondary' : '' }}" name="signal" value="helpful" type="submit">Helpful</button>
                    <button class="button {{ $currentRating === 'not_helpful' ? 'button-secondary' : '' }}" name="signal" value="not_helpful" type="submit">Not helpful</button>
                </form>
                @if ($currentRating !== null)
                    <form method="post" action="{{ route('projects.rating.destroy', $project->slug) }}">
                        @csrf
                        @method('delete')
                        <button class="link-button" type="submit">Remove feedback</button>
                    </form>
                @endif
            @else
                <a class="button" href="{{ route('login') }}">Log in to rate</a>
            @endauth
        </article>
        <article class="card">
            <h2>Feed Preference</h2>
            @auth
                @if ($isNotInterested)
                    <p>This project is marked as not interesting for your feed.</p>
                    <form method="post" action="{{ route('projects.interest-feedback.destroy', $project->slug) }}">
                        @csrf
                        @method('delete')
                        <button class="button button-secondary" type="submit">Show in feed again</button>
                    </form>
                @else
                    <p>This preference is private and only affects personalization.</p>
                    <form method="post" action="{{ route('projects.interest-feedback.store', $project->slug) }}">
                        @csrf
                        <button class="button button-secondary" type="submit">Not interested</button>
                    </form>
                @endif
            @else
                <a class="button" href="{{ route('login') }}">Log in to personalize</a>
            @endauth
        </article>
        <article class="card">
            <h2>Tags</h2>
            <div class="meta">
                @foreach ($project->tags ?? [] as $tag)
                    <span class="pill">{{ $tag }}</span>
                @endforeach
            </div>
        </article>
        <article class="card">
            <h2>Report Project</h2>
            <form class="form" method="post" action="{{ route('projects.reports.store', $project->slug) }}">
                @csrf
                <label>
                    Reason
                    <select name="reason" required>
                        @foreach (config('safedrop.report_reasons') as $reason)
                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ str_replace('_', ' ', $reason) }}</option>
                        @endforeach
                    </select>
                </label>
                @error('reason')
                    <p class="error">{{ $message }}</p>
                @enderror

                @guest
                    <label>
                        Email
                        <input name="reporter_email" type="email" value="{{ old('reporter_email') }}" autocomplete="email" maxlength="255">
                    </label>
                    @error('reporter_email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                @endguest

                <label>
                    Details
                    <textarea name="details" required minlength="10" maxlength="2000">{{ old('details') }}</textarea>
                </label>
                @error('details')
                    <p class="error">{{ $message }}</p>
                @enderror

                <button class="button" type="submit">Submit report</button>
            </form>
        </article>
    </section>
@endsection
