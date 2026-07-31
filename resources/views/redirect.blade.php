@extends('layouts.app', ['title' => 'Leaving Safedrop'])

@section('content')
    <section class="hero">
        <h1>External destination</h1>
        <p class="lede">
            Safedrop checked this destination before showing it. The MVP keeps this as a preview page before the final redirect service is implemented.
        </p>
    </section>

    <section class="notice">
        <p>You are about to leave Safedrop for <strong>{{ $target->target_domain }}</strong>.</p>
        <a class="button" href="{{ $target->safeDestinationUrl() }}" rel="nofollow noopener noreferrer">Open external site</a>
    </section>
@endsection
