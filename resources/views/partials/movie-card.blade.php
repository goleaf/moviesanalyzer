<article class="card overflow-hidden flex flex-col">
    <div class="aspect-[3/4] bg-black/50 border-b border-cine">
        @if ($group['poster_url'])
            <img src="{{ $group['poster_url'] }}" alt="{{ $group['title'] }}" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-xs text-muted">No poster</div>
        @endif
    </div>
    <div class="p-4 space-y-3">
        <div>
            <h3 class="font-cinema text-xl leading-tight">{{ $group['title'] }}</h3>
            <p class="text-xs text-muted mt-1">{{ $group['year'] ?: 'Year unknown' }}</p>
        </div>

        <div class="flex items-center justify-between text-xs">
            <span class="px-2 py-1 rounded-full bg-white/10">{{ $group['copies'] }} {{ Str::plural('copy', $group['copies']) }}</span>
            <span class="text-amber-300">★ {{ $group['vote_average'] ? number_format($group['vote_average'], 1) : 'n/a' }}</span>
        </div>

        <ul class="text-xs text-muted space-y-1">
            @foreach (collect($group['files'])->take(3) as $file)
                <li class="truncate" title="{{ $file->filename }}">{{ $file->filename }}</li>
            @endforeach
            @if (collect($group['files'])->count() > 3)
                <li>+{{ collect($group['files'])->count() - 3 }} more files</li>
            @endif
        </ul>

        <a
            href="{{ route('cineclean.movies.show', $group['primary_file_id']) }}"
            class="inline-flex items-center justify-center w-full rounded-lg border border-cine bg-black/30 px-3 py-2 text-xs uppercase tracking-wide hover:bg-white/5"
        >
            Open Details
        </a>
    </div>
</article>
