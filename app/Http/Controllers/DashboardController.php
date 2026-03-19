<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use App\Services\MovieLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(public MovieLibraryService $movieLibraryService)
    {
    }

    public function index(): View
    {
        $stats = $this->movieLibraryService->dashboardStats();
        $lastScan = ScanLog::query()
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
        $progress = Cache::get((string) config('cineclean.scan.progress_cache_key'), [
            'running' => false,
            'finished' => false,
            'status' => 'idle',
            'current' => 0,
            'total' => 0,
            'file' => null,
            'eta_seconds' => null,
            'matched' => 0,
            'unmatched' => 0,
        ]);

        return view('dashboard', [
            'stats' => $stats,
            'lastScan' => $lastScan,
            'scanProgress' => $progress,
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->movieLibraryService->dashboardStats());
    }
}
