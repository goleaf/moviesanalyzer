<?php

namespace App\Livewire\Dashboard;

use App\Actions\StartScanAction;
use App\Models\ScanLog;
use App\Services\MovieLibraryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OverviewPanel extends Component
{
    /**
     * @var array<string, int>
     */
    public array $stats = [
        'total_files' => 0,
        'duplicate_groups' => 0,
        'space_to_reclaim_bytes' => 0,
        'unmatched' => 0,
    ];

    public ?ScanLog $lastScan = null;

    /**
     * @var array<string, mixed>
     */
    public array $scanProgress = [];

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $movieLibraryService = app(MovieLibraryService::class);

        $this->stats = $movieLibraryService->dashboardStats();
        $this->lastScan = ScanLog::query()
            ->select([
                'id',
                'started_at',
                'finished_at',
                'total_files',
                'matched',
                'unmatched',
                'status',
                'notes',
            ])
            ->latest('started_at')
            ->first();

        /** @var array<string, mixed> $progress */
        $progress = Cache::get((string) config('cineclean.scan.progress_cache_key'), $this->defaultProgressState());
        $this->scanProgress = array_replace($this->defaultProgressState(), $progress);
    }

    public function startScan(): void
    {
        $result = app(StartScanAction::class)->handle(true);

        if ($result['already_running'] === true) {
            $this->dispatch('notify', message: 'A scan is already running.', type: 'info');
            $this->dispatch('scan-stream-required');
            $this->refreshData();

            return;
        }

        $this->dispatch('notify', message: 'Library scan queued.', type: 'success');
        $this->dispatch('scan-stream-required');
        $this->refreshData();
    }

    #[Computed]
    public function progressPercent(): int
    {
        $current = (int) ($this->scanProgress['current'] ?? 0);
        $total = (int) ($this->scanProgress['total'] ?? 0);

        if ($total <= 0) {
            return 0;
        }

        return min(100, (int) floor(($current / max(1, $total)) * 100));
    }

    #[Computed]
    public function scanIsActive(): bool
    {
        return (bool) ($this->scanProgress['running'] ?? false)
            || (($this->scanProgress['status'] ?? '') === 'queued');
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview-panel');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultProgressState(): array
    {
        return [
            'running' => false,
            'finished' => false,
            'status' => 'idle',
            'current' => 0,
            'total' => 0,
            'file' => null,
            'eta_seconds' => null,
            'matched' => 0,
            'unmatched' => 0,
            'started_at' => null,
            'finished_at' => null,
            'requested_at' => null,
            'heartbeat_at' => null,
        ];
    }
}
