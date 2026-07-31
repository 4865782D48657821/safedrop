@extends('layouts.app', ['title' => 'Moderation Queue | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Moderation Queue</h1>
        <p class="lede">Review projects, releases, and external destinations before they become eligible for discovery or redirects.</p>
    </section>

    <section class="grid">
        @forelse ($cases as $case)
            <article class="card">
                @php($subject = $case->subject)
                <h2>{{ $case->subjectLabel() }}</h2>
                <div class="meta">
                    <span class="pill">{{ str_replace('_', ' ', $case->category) }}</span>
                    <span class="pill">{{ $case->risk_level }}</span>
                    <span class="pill">{{ $case->status }}</span>
                </div>
                @if ($case->reason)
                    <p>{{ $case->reason }}</p>
                @endif

                @if ($subject instanceof \App\Models\Project)
                    <dl class="details">
                        <div>
                            <dt>Summary</dt>
                            <dd>{{ $subject->summary }}</dd>
                        </div>
                        <div>
                            <dt>Game</dt>
                            <dd>{{ ucfirst($subject->game) }}</dd>
                        </div>
                        <div>
                            <dt>Project type</dt>
                            <dd>{{ str_replace('_', ' ', $subject->project_type) }}</dd>
                        </div>
                        <div>
                            <dt>Moderation</dt>
                            <dd>{{ $subject->moderation_status }}</dd>
                        </div>
                    </dl>
                @elseif ($subject instanceof \App\Models\Release)
                    <dl class="details">
                        <div>
                            <dt>Project</dt>
                            <dd>{{ $subject->project?->title ?? 'Deleted project' }}</dd>
                        </div>
                        <div>
                            <dt>Version</dt>
                            <dd>{{ $subject->version }}</dd>
                        </div>
                        <div>
                            <dt>Published</dt>
                            <dd>{{ $subject->published_at?->toDateString() ?? 'Not published' }}</dd>
                        </div>
                        <div>
                            <dt>Moderation</dt>
                            <dd>{{ $subject->moderation_status }}</dd>
                        </div>
                    </dl>
                @elseif ($subject instanceof \App\Models\ExternalTarget)
                    <dl class="details">
                        <div>
                            <dt>URL</dt>
                            <dd>{{ $subject->normalized_url ?? $subject->original_url }}</dd>
                        </div>
                        <div>
                            <dt>Domain</dt>
                            <dd>{{ $subject->target_domain }}</dd>
                        </div>
                        <div>
                            <dt>Domain status</dt>
                            <dd>{{ $subject->domain_status->value }}</dd>
                        </div>
                        <div>
                            <dt>Reachability</dt>
                            <dd>{{ $subject->reachability_status }}</dd>
                        </div>
                    </dl>
                @endif

                <form class="form" method="post" action="{{ route('moderation.decide', $case) }}">
                    @csrf
                    <label>
                        Note
                        <input name="note" autocomplete="off" maxlength="1000">
                    </label>
                    <div class="meta">
                        <button class="button" type="submit" name="action" value="approve">Approve</button>
                        <button class="button" type="submit" name="action" value="needs_review">Needs review</button>
                        <button class="button" type="submit" name="action" value="block">Block</button>
                    </div>
                </form>
            </article>
        @empty
            <article class="card">
                <h2>No open cases</h2>
                <p>The moderation queue is clear.</p>
            </article>
        @endforelse
    </section>
@endsection
