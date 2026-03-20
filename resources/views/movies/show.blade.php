@extends('layouts.app')

@section('title', ($movie['title'] ?? 'Movie Details').' · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6 space-y-4">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-muted">Movie Details</p>
                    <h2 class="font-cinema text-3xl mt-1">
                        {{ $movie['title'] ?? 'Unknown movie' }}
                        <span class="text-muted text-2xl">({{ $movie['year'] ?? 'n/a' }})</span>
                    </h2>
                    @if (!empty($movie['tagline']))
                        <p class="text-sm text-muted mt-2">{{ $movie['tagline'] }}</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('cineclean.movies.sync-details', $movieFile) }}">
                    @csrf
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30 text-sm"
                    >
                        Sync Full TMDB Details
                    </button>
                </form>
            </div>

            <div class="grid gap-4 text-sm md:grid-cols-3">
                <div class="rounded-xl border border-cine p-3">
                    <p class="text-xs uppercase tracking-wide text-muted">TMDB</p>
                    <p class="mt-1">#{{ $movie['tmdb_id'] }}</p>
                    @if (!empty($movie['tmdb_url']))
                        <a
                            href="{{ $movie['tmdb_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-amber-300 text-xs hover:text-amber-200 underline underline-offset-2"
                        >
                            Open on TMDB
                        </a>
                    @endif
                </div>
                <div class="rounded-xl border border-cine p-3">
                    <p class="text-xs uppercase tracking-wide text-muted">Rating</p>
                    <p class="mt-1">★ {{ isset($movie['vote_average']) ? number_format((float) $movie['vote_average'], 1) : 'n/a' }}</p>
                    <p class="text-xs text-muted">{{ $movie['vote_count'] ?? 0 }} votes</p>
                </div>
                <div class="rounded-xl border border-cine p-3">
                    <p class="text-xs uppercase tracking-wide text-muted">Copies in Library</p>
                    <p class="mt-1">{{ $movie['copies'] }} {{ Str::plural('file', (int) $movie['copies']) }}</p>
                    <p class="text-xs text-muted">{{ $movie['total_size_formatted'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[280px,1fr] gap-6">
            <div class="card overflow-hidden">
                <div class="aspect-[2/3] bg-black/50">
                    @if (!empty($movie['poster_url']))
                        <img src="{{ $movie['poster_url'] }}" alt="{{ $movie['title'] }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-xs text-muted">No poster</div>
                    @endif
                </div>
            </div>

            <div class="card p-4 md:p-6 space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-muted">Overview</p>
                    <p class="mt-2 leading-relaxed">{{ $movie['overview'] ?: 'Overview is not available for this movie.' }}</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2 text-sm">
                    <div class="rounded-xl border border-cine p-3">
                        <p class="text-xs uppercase tracking-wide text-muted">Release Date</p>
                        <p class="mt-1">{{ $movie['release_date'] ?? 'n/a' }}</p>
                    </div>
                    <div class="rounded-xl border border-cine p-3">
                        <p class="text-xs uppercase tracking-wide text-muted">Runtime</p>
                        <p class="mt-1">{{ $movie['runtime'] ? $movie['runtime'].' min' : 'n/a' }}</p>
                    </div>
                    <div class="rounded-xl border border-cine p-3">
                        <p class="text-xs uppercase tracking-wide text-muted">Status</p>
                        <p class="mt-1">{{ $movie['status'] ?? 'n/a' }}</p>
                    </div>
                    <div class="rounded-xl border border-cine p-3">
                        <p class="text-xs uppercase tracking-wide text-muted">IMDb</p>
                        <p class="mt-1">{{ $movie['imdb_id'] ?? 'n/a' }}</p>
                    </div>
                </div>

                @if ($movie['genres'] !== [])
                    <div>
                        <p class="text-xs uppercase tracking-wide text-muted">Genres</p>
                        <p class="mt-1 text-sm">{{ implode(', ', $movie['genres']) }}</p>
                    </div>
                @endif

                @if ($movie['keywords'] !== [])
                    <div>
                        <p class="text-xs uppercase tracking-wide text-muted">Keywords</p>
                        <p class="mt-1 text-sm">{{ implode(', ', array_slice($movie['keywords'], 0, 20)) }}</p>
                    </div>
                @endif

                @if ($movie['watch_providers'] !== [])
                    <div>
                        <p class="text-xs uppercase tracking-wide text-muted">Watch Providers</p>
                        <p class="mt-1 text-sm">{{ implode(', ', $movie['watch_providers']) }}</p>
                    </div>
                @endif

                @if ($movie['cast'] !== [])
                    <div>
                        <p class="text-xs uppercase tracking-wide text-muted">Cast (Top)</p>
                        <p class="mt-1 text-sm">{{ implode(', ', array_slice($movie['cast'], 0, 15)) }}</p>
                    </div>
                @endif

                @if ($movie['budget'] !== null || $movie['revenue'] !== null)
                    <div class="grid gap-3 md:grid-cols-2 text-sm">
                        <div class="rounded-xl border border-cine p-3">
                            <p class="text-xs uppercase tracking-wide text-muted">Budget</p>
                            <p class="mt-1">{{ $movie['budget'] !== null ? '$'.number_format($movie['budget']) : 'n/a' }}</p>
                        </div>
                        <div class="rounded-xl border border-cine p-3">
                            <p class="text-xs uppercase tracking-wide text-muted">Revenue</p>
                            <p class="mt-1">{{ $movie['revenue'] !== null ? '$'.number_format($movie['revenue']) : 'n/a' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card p-4 md:p-6">
            <h3 class="font-cinema text-2xl">Local TMDB Images</h3>
            <p class="text-xs text-muted mt-1">All downloaded images are saved under `storage/app/public/tmdb/movies`.</p>

            <div class="mt-4 space-y-5">
                <section class="space-y-2">
                    <h4 class="text-sm uppercase tracking-wide text-muted">Posters</h4>
                    @if ($movie['local_posters'] !== [])
                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
                            @foreach ($movie['local_posters'] as $poster)
                                <div class="rounded-lg border border-cine overflow-hidden bg-black/40">
                                    <img
                                        src="{{ $poster['local_url'] ?? '' }}"
                                        alt="{{ $movie['title'] }} poster"
                                        loading="lazy"
                                        class="w-full aspect-[2/3] object-cover"
                                    >
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-muted">No local posters downloaded yet.</p>
                    @endif
                </section>

                <section class="space-y-2">
                    <h4 class="text-sm uppercase tracking-wide text-muted">Backdrops</h4>
                    @if ($movie['local_backdrops'] !== [])
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach ($movie['local_backdrops'] as $backdrop)
                                <div class="rounded-lg border border-cine overflow-hidden bg-black/40">
                                    <img
                                        src="{{ $backdrop['local_url'] ?? '' }}"
                                        alt="{{ $movie['title'] }} backdrop"
                                        loading="lazy"
                                        class="w-full aspect-video object-cover"
                                    >
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-muted">No local backdrops downloaded yet.</p>
                    @endif
                </section>

                <section class="space-y-2">
                    <h4 class="text-sm uppercase tracking-wide text-muted">Logos</h4>
                    @if ($movie['local_logos'] !== [])
                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
                            @foreach ($movie['local_logos'] as $logo)
                                <div class="rounded-lg border border-cine overflow-hidden bg-black/40 p-2">
                                    <img
                                        src="{{ $logo['local_url'] ?? '' }}"
                                        alt="{{ $movie['title'] }} logo"
                                        loading="lazy"
                                        class="w-full h-20 object-contain"
                                    >
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-muted">No local logos downloaded yet.</p>
                    @endif
                </section>
            </div>
        </div>

        <div class="card p-4 md:p-6">
            <h3 class="font-cinema text-2xl">Copies in Library</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-muted border-b border-cine">
                    <tr>
                        <th class="px-3 py-2">Filename</th>
                        <th class="px-3 py-2">Path</th>
                        <th class="px-3 py-2">Size</th>
                        <th class="px-3 py-2">Extension</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($movie['files'] as $file)
                        <tr class="border-b border-cine/60">
                            <td class="px-3 py-2">{{ $file->filename }}</td>
                            <td class="px-3 py-2 text-xs text-muted break-all">{{ $file->smb_path }}</td>
                            <td class="px-3 py-2">{{ $file->formatted_size }}</td>
                            <td class="px-3 py-2 uppercase">{{ $file->extension }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
