@extends('layouts.app', ['title' => 'Creator Tools | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Creator Tools</h1>
        <p class="lede">Prepare project metadata, releases, and reviewed external destinations.</p>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Publishing</h2>
            <p>Project publishing is available for {{ str_replace('_', ' ', $user->role->value) }} accounts.</p>
        </article>
        <article class="card">
            <h2>Revenue Safety</h2>
            <p>{{ $user->canShowRevenueAdsOnProjectPages() ? 'Revenue ads may be shown after review.' : 'Revenue ads are disabled for this account.' }}</p>
        </article>
    </section>
@endsection
