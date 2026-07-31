@extends('layouts.app', ['title' => 'Moderation Queue | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Moderation Queue</h1>
        <p class="lede">Review projects, releases, and external destinations before they become eligible for discovery or redirects.</p>
    </section>

    <section class="grid">
        @forelse ($cases as $case)
            <article class="card">
                <h2>{{ $case->subjectLabel() }}</h2>
                <div class="meta">
                    <span class="pill">{{ str_replace('_', ' ', $case->category) }}</span>
                    <span class="pill">{{ $case->risk_level }}</span>
                    <span class="pill">{{ $case->status }}</span>
                </div>
                @if ($case->reason)
                    <p>{{ $case->reason }}</p>
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
