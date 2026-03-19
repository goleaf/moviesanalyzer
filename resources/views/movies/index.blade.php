@extends('layouts.app')

@section('title', 'All Movies · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <h2 class="font-cinema text-3xl">All Matched Movies</h2>
            <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search by title or filename"
                    class="flex-1 rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                >
                <button type="submit" class="px-5 py-2 rounded-lg border border-cine bg-card hover:bg-white/5">Search</button>
            </form>
        </div>

        @if ($movies->count() > 0)
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @foreach ($movies as $group)
                    @include('partials.movie-card', ['group' => $group])
                @endforeach
            </div>

            <div class="card p-4">
                {{ $movies->links() }}
            </div>
        @else
            <div class="card p-8 text-center">
                <p class="font-cinema text-2xl">No matched movies yet.</p>
                <p class="text-muted mt-2">Run a scan to populate this view.</p>
            </div>
        @endif
    </section>
@endsection
