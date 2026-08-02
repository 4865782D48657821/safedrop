@extends('layouts.app', ['title' => 'Feed Interests | Safedrop'])

@section('content')
    @php($projectTypes = array_values(array_unique(array_merge(...array_column($games, 'project_types')))))

    <section class="hero">
        <h1>Feed Interests</h1>
        <p class="lede">Personalize reviewed project discovery with lightweight interest signals.</p>
    </section>

    <form class="form" method="post" action="{{ route('onboarding.update') }}">
        @csrf
        @method('put')

        <section>
            <h2>Games</h2>
            <div class="grid">
                @foreach ($games as $key => $game)
                    <label class="card">
                        <input type="checkbox" name="games[]" value="{{ $key }}" @checked(in_array($key, old('games', $preference?->games ?? []), true))>
                        <strong>{{ $game['label'] }}</strong>
                    </label>
                @endforeach
            </div>
            @error('games')
                <p class="error">{{ $message }}</p>
            @enderror
        </section>

        <section>
            <h2>Project Types</h2>
            <div class="grid">
                @foreach ($projectTypes as $type)
                    <label class="card">
                        <input type="checkbox" name="project_types[]" value="{{ $type }}" @checked(in_array($type, old('project_types', $preference?->project_types ?? []), true))>
                        <strong>{{ str_replace('_', ' ', ucfirst($type)) }}</strong>
                    </label>
                @endforeach
            </div>
            @error('project_types')
                <p class="error">{{ $message }}</p>
            @enderror
        </section>

        <section>
            <h2>Categories</h2>
            <div class="grid">
                @foreach ($options['categories'] as $category)
                    <label class="card">
                        <input type="checkbox" name="categories[]" value="{{ $category }}" @checked(in_array($category, old('categories', $preference?->categories ?? []), true))>
                        <strong>{{ str_replace('_', ' ', ucfirst($category)) }}</strong>
                    </label>
                @endforeach
            </div>
            @error('categories')
                <p class="error">{{ $message }}</p>
            @enderror
        </section>

        <section>
            <h2>Versions And Platforms</h2>
            <div class="grid">
                @foreach ($options['versions'] as $version)
                    <label class="card">
                        <input type="checkbox" name="versions[]" value="{{ $version }}" @checked(in_array($version, old('versions', $preference?->versions ?? []), true))>
                        <strong>{{ str_replace(':', ' ', ucfirst($version)) }}</strong>
                    </label>
                @endforeach
                @foreach ($options['platforms'] as $platform)
                    <label class="card">
                        <input type="checkbox" name="platforms[]" value="{{ $platform }}" @checked(in_array($platform, old('platforms', $preference?->platforms ?? []), true))>
                        <strong>{{ str_replace('_', ' ', ucfirst($platform)) }}</strong>
                    </label>
                @endforeach
            </div>
            @error('versions')
                <p class="error">{{ $message }}</p>
            @enderror
            @error('platforms')
                <p class="error">{{ $message }}</p>
            @enderror
        </section>

        <section>
            <h2>Known Creators</h2>
            <div class="grid">
                @forelse ($knownCreators as $creator)
                    <label class="card">
                        <input type="checkbox" name="creator_ids[]" value="{{ $creator->id }}" @checked(in_array($creator->id, array_map('intval', old('creator_ids', $preference?->creator_ids ?? [])), true))>
                        <strong>{{ $creator->name }}</strong>
                    </label>
                @empty
                    <article class="card">
                        <h3>No reviewed creators yet</h3>
                        <p>Creator choices appear after public projects pass review.</p>
                    </article>
                @endforelse
            </div>
            @error('creator_ids')
                <p class="error">{{ $message }}</p>
            @enderror
        </section>

        <div class="actions">
            <button class="button" type="submit">Save interests</button>
            <a href="{{ route('home') }}">Cancel</a>
        </div>
    </form>

    <form method="post" action="{{ route('onboarding.skip') }}">
        @csrf
        <button class="button button-secondary" type="submit">Skip for now</button>
    </form>
@endsection
