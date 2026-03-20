<section class="space-y-6" wire:poll.3s.visible="refreshData">
    <div class="card p-5 md:p-7">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-muted">Operations Center</p>
                <h2 class="font-cinema mt-2 text-3xl md:text-4xl">SMB Movie Intelligence</h2>
                <p class="mt-2 max-w-3xl text-sm text-muted">
                    Track scan progress in real-time, prioritize duplicate cleanup, and keep TMDB metadata synchronized.
                </p>
            </div>
            <button
                type="button"
                class="btn btn-primary px-8 py-3 text-xs font-semibold tracking-[0.18em]"
                wire:click="startScan"
                @disabled($this->scanIsActive)
            >
                SCAN LIBRARY
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="card p-5">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Total Files</p>
            <p class="font-cinema mt-2 text-3xl">{{ number_format($stats['total_files'] ?? 0) }}</p>
        </article>
        <article class="card p-5">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Duplicate Groups</p>
            <p class="font-cinema mt-2 text-3xl">{{ number_format($stats['duplicate_groups'] ?? 0) }}</p>
        </article>
        <article class="card p-5">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Reclaimable Space</p>
            <p class="font-cinema mt-2 text-3xl">{{ \App\Models\MovieFile::formatBytes((int) ($stats['space_to_reclaim_bytes'] ?? 0)) }}</p>
        </article>
        <article class="card p-5">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Unmatched</p>
            <p class="font-cinema mt-2 text-3xl">{{ number_format($stats['unmatched'] ?? 0) }}</p>
        </article>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <article class="card p-5 md:p-6">
            <div class="flex items-center justify-between gap-3">
                <h3 class="font-cinema text-2xl">Live Scan Progress</h3>
                <span @class([
                    'rounded-full border px-3 py-1 text-xs uppercase tracking-[0.14em]',
                    'border-emerald-400/40 bg-emerald-700/25 text-emerald-200' => ($scanProgress['status'] ?? '') === 'complete',
                    'border-amber-400/50 bg-amber-700/25 text-amber-200' => ($scanProgress['status'] ?? '') === 'queued',
                    'border-sky-400/40 bg-sky-700/20 text-sky-200' => ($scanProgress['running'] ?? false) === true,
                    'border-slate-500/50 bg-slate-700/25 text-slate-300' => in_array(($scanProgress['status'] ?? ''), ['idle', 'skipped'], true),
                ])>
                    {{ strtoupper((string) ($scanProgress['status'] ?? 'idle')) }}
                </span>
            </div>

            <div class="mt-5 h-3 overflow-hidden rounded-full border border-cine bg-slate-950/70">
                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400 transition-all duration-500" style="width: {{ $this->progressPercent }}%;"></div>
            </div>

            <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                <p>
                    <span class="text-muted">Progress:</span>
                    {{ (int) ($scanProgress['current'] ?? 0) }} / {{ (int) ($scanProgress['total'] ?? 0) }}
                </p>
                <p>
                    <span class="text-muted">Matched / Unmatched:</span>
                    {{ (int) ($scanProgress['matched'] ?? 0) }} / {{ (int) ($scanProgress['unmatched'] ?? 0) }}
                </p>
                <p class="md:col-span-2 break-all">
                    <span class="text-muted">Current File:</span>
                    {{ $scanProgress['file'] ?? 'Waiting for next scan event...' }}
                </p>
            </div>

            @if (($scanProgress['eta_seconds'] ?? null) !== null && (int) $scanProgress['eta_seconds'] > 0)
                <p class="mt-3 text-xs text-muted">
                    ETA {{ gmdate('i:s', (int) $scanProgress['eta_seconds']) }}
                </p>
            @endif
        </article>

        <article class="card p-5 md:p-6">
            <h3 class="font-cinema text-2xl">Last Scan Snapshot</h3>
            @if ($lastScan)
                <div class="mt-4 space-y-3 text-sm">
                    <p><span class="text-muted">Started:</span> {{ $lastScan->started_at?->format('Y-m-d H:i:s') }}</p>
                    <p><span class="text-muted">Finished:</span> {{ $lastScan->finished_at?->format('Y-m-d H:i:s') ?? 'In progress' }}</p>
                    <p><span class="text-muted">Status:</span> {{ ucfirst((string) $lastScan->status) }}</p>
                    <p><span class="text-muted">Total:</span> {{ number_format((int) $lastScan->total_files) }}</p>
                    <p><span class="text-muted">Matched / Unmatched:</span> {{ number_format((int) $lastScan->matched) }} / {{ number_format((int) $lastScan->unmatched) }}</p>
                </div>
            @else
                <p class="mt-4 text-sm text-muted">No scans have been run yet.</p>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('cineclean.duplicates.index') }}" class="btn btn-ghost text-xs">Review Duplicates</a>
                <a href="{{ route('cineclean.unmatched.index') }}" class="btn btn-ghost text-xs">Resolve Unmatched</a>
            </div>
        </article>
    </div>
</section>

@script
<script>
    let scanStream = null;
    const scanProgressUrl = @js(route('cineclean.scan.progress'));

    const startScanStream = () => {
        if (scanStream !== null) {
            return;
        }

        scanStream = new EventSource(scanProgressUrl);
        scanStream.addEventListener('progress', (event) => {
            try {
                const payload = JSON.parse(event.data);

                if (payload?.finished === true && scanStream !== null) {
                    scanStream.close();
                    scanStream = null;
                }
            } catch (error) {
                if (scanStream !== null) {
                    scanStream.close();
                    scanStream = null;
                }
            }

            $wire.refreshData();
        });

        scanStream.onerror = () => {
            if (scanStream !== null) {
                scanStream.close();
                scanStream = null;
            }
        };
    };

    if (@js($this->scanIsActive)) {
        startScanStream();
    }

    window.addEventListener('scan-stream-required', startScanStream);
</script>
@endscript
