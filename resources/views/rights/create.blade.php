@extends('layouts.app', ['title' => 'Rights Case | Safedrop'])

@section('content')
    <section class="hero">
        <h1>Rights Case</h1>
        <p class="lede">Submit a copyright, trademark, or ownership concern for moderator review.</p>
    </section>

    @if (session('status'))
        <section class="notice">{{ session('status') }}</section>
    @endif

    <form class="form" method="post" action="{{ route('rights.store') }}">
        @csrf
        <label>
            Project
            <select name="project_id">
                <option value="">Not tied to a listed project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->title }}</option>
                @endforeach
            </select>
        </label>
        @error('project_id')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Name
            <input name="claimant_name" value="{{ old('claimant_name') }}" autocomplete="name" required maxlength="120">
        </label>
        @error('claimant_name')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Email
            <input name="claimant_email" type="email" value="{{ old('claimant_email') }}" autocomplete="email" required maxlength="255">
        </label>
        @error('claimant_email')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Claim type
            <select name="claim_type" required>
                @foreach (config('safedrop.rights_claim_types') as $type)
                    <option value="{{ $type }}" @selected(old('claim_type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                @endforeach
            </select>
        </label>
        @error('claim_type')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Details
            <textarea name="details" required minlength="20" maxlength="3000">{{ old('details') }}</textarea>
        </label>
        @error('details')
            <p class="error">{{ $message }}</p>
        @enderror

        <button class="button" type="submit">Submit case</button>
    </form>
@endsection
