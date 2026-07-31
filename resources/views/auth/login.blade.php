@extends('layouts.app', ['title' => 'Login | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Login</h1>
        <p class="lede">Access personalization, follows, saves, ratings, and creator tools.</p>
    </section>

    <form class="form" method="post" action="{{ route('login.store') }}">
        @csrf

        <label>
            Email
            <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        </label>
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Password
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror

        <button class="button" type="submit">Login</button>
    </form>
@endsection
