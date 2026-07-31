@extends('layouts.app', ['title' => 'Leaving Safedrop'])

@section('content')
    <section class="hero">
        <h1>External destination</h1>
        <p class="lede">
            Safedrop checked this destination before showing it. The continue link is short lived and tied to this reviewed target.
        </p>
    </section>

    <section class="notice">
        <p>You are about to leave Safedrop for <strong>{{ $target->target_domain }}</strong>.</p>
        <p>Destination: {{ $target->publicDestinationUrl() }}</p>
        <a class="button" href="{{ $signedRedirectUrl }}" rel="nofollow noopener noreferrer">Open external site</a>
    </section>
@endsection
