@extends('layouts.app', ['title' => 'Register | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Register</h1>
        <p class="lede">Create a member account. Creator and monetization roles are assigned through server-side review flows.</p>
    </section>

    <form class="form" method="post" action="{{ route('register.store') }}">
        @csrf

        <label>
            Name
            <input name="name" type="text" value="{{ old('name') }}" autocomplete="name" required>
        </label>
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Email
            <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        </label>
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Password
            <input name="password" type="password" autocomplete="new-password" required>
        </label>
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Confirm Password
            <input name="password_confirmation" type="password" autocomplete="new-password" required>
        </label>

        <button class="button" type="submit">Create account</button>
    </form>
@endsection
