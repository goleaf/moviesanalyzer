@php
    $items = [
        ['label' => 'Total Files', 'value' => number_format($stats['total_files'] ?? 0)],
        ['label' => 'Duplicate Groups', 'value' => number_format($stats['duplicate_groups'] ?? 0)],
        ['label' => 'Space to Reclaim', 'value' => \App\Models\MovieFile::formatBytes((int) ($stats['space_to_reclaim_bytes'] ?? 0))],
        ['label' => 'Unmatched', 'value' => number_format($stats['unmatched'] ?? 0)],
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @foreach ($items as $item)
        <article class="card p-5">
            <p class="text-xs uppercase tracking-widest text-muted">{{ $item['label'] }}</p>
            <p class="font-cinema text-3xl mt-2">{{ $item['value'] }}</p>
        </article>
    @endforeach
</div>
