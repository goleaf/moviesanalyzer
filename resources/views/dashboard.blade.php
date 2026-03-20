@extends('layouts.app')

@section('title', 'moviesanalyzer Dashboard')

@section('content')
    <section class="space-y-6">
        @include('partials.stats-bar', ['stats' => $stats])

        <div class="card p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-cinema text-3xl">Scan Library</h2>
                    <p class="text-muted text-sm mt-2">Scan SMB files, resolve TMDB identity, and surface duplicates for manual review.</p>
                </div>

                <form method="POST" action="{{ route('cineclean.scan.start') }}" id="scan-form">
                    @csrf
                    <input type="hidden" name="rescan_all" value="1">
                    <button type="submit" class="px-8 py-4 text-sm font-semibold tracking-wider rounded-xl bg-amber-500/90 text-black hover:bg-amber-400 transition shadow-lg shadow-amber-900/30">
                        SCAN LIBRARY
                    </button>
                </form>
            </div>

            <div class="mt-8 space-y-3" id="scan-panel">
                <div class="h-3 rounded-full bg-black/40 border border-cine overflow-hidden">
                    <div id="scan-progress-bar" class="h-full bg-amber-500 transition-all duration-500" style="width: {{ ($scanProgress['total'] ?? 0) > 0 ? ((int) (($scanProgress['current'] / max(1, $scanProgress['total'])) * 100)).'%' : '0%' }};"></div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 text-sm">
                    <p id="scan-status">Status: {{ ucfirst($scanProgress['status'] ?? 'idle') }}</p>
                    <p class="text-muted" id="scan-file">{{ $scanProgress['file'] ? 'File: '.$scanProgress['file'] : 'Waiting for scan...' }}</p>
                </div>
                <p class="text-xs text-muted" id="scan-meta">
                    Progress: {{ (int) ($scanProgress['current'] ?? 0) }} / {{ (int) ($scanProgress['total'] ?? 0) }}
                    @if (!empty($scanProgress['eta_seconds']))
                        · ETA {{ gmdate('i:s', (int) $scanProgress['eta_seconds']) }}
                    @endif
                </p>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="font-cinema text-2xl">Last Scan</h3>
            @if ($lastScan)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                    <div>
                        <p class="text-muted text-xs uppercase">Started</p>
                        <p>{{ $lastScan->started_at?->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-muted text-xs uppercase">Finished</p>
                        <p>{{ $lastScan->finished_at?->format('Y-m-d H:i:s') ?? 'In progress' }}</p>
                    </div>
                    <div>
                        <p class="text-muted text-xs uppercase">Total Files</p>
                        <p>{{ number_format($lastScan->total_files) }}</p>
                    </div>
                    <div>
                        <p class="text-muted text-xs uppercase">Matched / Unmatched</p>
                        <p>{{ number_format($lastScan->matched) }} / {{ number_format($lastScan->unmatched) }}</p>
                    </div>
                </div>
            @else
                <p class="text-muted mt-3">No scans have been run yet.</p>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const shouldAutoStart = @json(session('scan_requested') || (($scanProgress['running'] ?? false) === true) || (($scanProgress['status'] ?? null) === 'queued'));
            const statusEl = document.getElementById('scan-status');
            const fileEl = document.getElementById('scan-file');
            const metaEl = document.getElementById('scan-meta');
            const barEl = document.getElementById('scan-progress-bar');
            let stream = null;
            let reconnectTimer = null;
            let reconnectErrors = 0;
            let scanFinished = false;

            const closeStream = () => {
                if (stream) {
                    stream.close();
                    stream = null;
                }

                if (reconnectTimer) {
                    clearTimeout(reconnectTimer);
                    reconnectTimer = null;
                }
            };

            const updateProgress = (payload) => {
                const total = Number(payload.total || 0);
                const current = Number(payload.current || 0);
                const percent = total > 0 ? Math.min(100, Math.round((current / total) * 100)) : 0;
                barEl.style.width = `${percent}%`;
                statusEl.textContent = `Status: ${String(payload.status || 'idle').replace(/(^\w)/, c => c.toUpperCase())}`;
                fileEl.textContent = payload.file ? `File: ${payload.file}` : 'Waiting for scan...';

                const eta = Number(payload.eta_seconds || 0);
                const etaText = eta > 0 ? ` · ETA ${new Date(eta * 1000).toISOString().substring(14, 19)}` : '';
                metaEl.textContent = `Progress: ${current} / ${total}${etaText}`;

                if (payload.finished === true) {
                    if (payload.status === 'failed') {
                        window.MoviesAnalyzer.toast(payload.error || 'Scan failed.', 'error');
                    } else {
                        window.MoviesAnalyzer.toast('Scan finished. Redirecting to duplicates...', 'success');
                        setTimeout(() => window.location.href = @json(route('cineclean.duplicates.index')), 1200);
                    }

                    scanFinished = true;
                    closeStream();
                }
            };

            const connectStream = () => {
                if (stream || scanFinished) {
                    return;
                }

                stream = new EventSource(@json(route('cineclean.scan.progress')));
                stream.addEventListener('progress', (event) => {
                    try {
                        const payload = JSON.parse(event.data);
                        reconnectErrors = 0;
                        updateProgress(payload);
                    } catch (error) {
                        console.error(error);
                    }
                });
                stream.onerror = () => {
                    closeStream();

                    if (scanFinished) {
                        return;
                    }

                    reconnectErrors += 1;

                    if (reconnectErrors >= 3) {
                        window.MoviesAnalyzer.toast('Reconnecting to scan progress...', 'info');
                        reconnectErrors = 0;
                    }

                    reconnectTimer = setTimeout(() => {
                        reconnectTimer = null;
                        connectStream();
                    }, 1500);
                };
            };

            if (shouldAutoStart) {
                connectStream();
            }
        })();
    </script>
@endpush
