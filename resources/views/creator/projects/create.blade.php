@extends('layouts.app', ['title' => 'New Project | Safedrop'])

@section('content')
    <section class="hero">
        <h1>New Project</h1>
        <p class="lede">Submit project metadata, an initial release, and one reviewed external destination.</p>
    </section>

    <form class="form" method="post" action="{{ route('creator.projects.store') }}">
        @csrf

        <label>
            Title
            <input name="title" value="{{ old('title') }}" maxlength="120" required>
        </label>
        @error('title')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Summary
            <textarea name="summary" maxlength="500" required>{{ old('summary') }}</textarea>
        </label>
        @error('summary')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Description
            <textarea name="description" maxlength="5000">{{ old('description') }}</textarea>
        </label>
        @error('description')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Game
            <select name="game" required>
                @foreach ($games as $key => $game)
                    <option value="{{ $key }}" @selected(old('game') === $key)>{{ $game['label'] }}</option>
                @endforeach
            </select>
        </label>
        @error('game')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Project type
            <select name="project_type" required>
                @foreach ($games as $game)
                    @foreach ($game['project_types'] as $type)
                        <option value="{{ $type }}" @selected(old('project_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                    @endforeach
                @endforeach
            </select>
        </label>
        @error('project_type')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Tags
            <input name="tags" value="{{ old('tags') }}" maxlength="240">
        </label>
        @error('tags')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            Version
            <input name="version" value="{{ old('version', '1.0.0') }}" maxlength="60" required>
        </label>
        @error('version')
            <p class="error">{{ $message }}</p>
        @enderror

        <label>
            External project URL
            <input name="external_url" value="{{ old('external_url') }}" maxlength="2048" required>
        </label>
        @error('external_url')
            <p class="error">{{ $message }}</p>
        @enderror

        <button class="button" type="submit">Submit for moderation</button>
    </form>
@endsection
