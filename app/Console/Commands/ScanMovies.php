<?php

namespace App\Console\Commands;

use App\Actions\ScanMovieLibraryAction;
use App\Models\MovieFile;
use App\Services\MovieLibraryService;
use Illuminate\Console\Command;

class ScanMovies extends Command
{
    protected $signature = 'movies:scan';

    protected $description = 'Scan SMB movie library and match files to TMDB.';

    public function handle(ScanMovieLibraryAction $scanMovieLibraryAction, MovieLibraryService $movieLibraryService): int
    {
        $this->line(sprintf(
            'Scanning smb://%s/%s/%s/...',
            config('cineclean.smb.host'),
            config('cineclean.smb.share'),
            config('cineclean.smb.path'),
        ));

        $progressBar = null;

        $scanLog = $scanMovieLibraryAction->handle(function (array $payload) use (&$progressBar): void {
            $total = (int) ($payload['total'] ?? 0);

            if ($total > 0 && $progressBar === null) {
                $progressBar = $this->output->createProgressBar($total);
                $progressBar->setFormat(' [%bar%] %percent:3s%% %message%');
                $progressBar->setMessage('Starting scan');
                $progressBar->start();
            }

            if ($progressBar !== null) {
                $status = ucfirst((string) ($payload['status'] ?? 'scanning'));
                $file = (string) ($payload['file'] ?? '...');
                $progressBar->setMessage(sprintf('%s: %s', $status, $file));
                $progressBar->setProgress((int) ($payload['current'] ?? 0));
            }
        });

        if ($progressBar !== null) {
            $progressBar->finish();
            $this->newLine(2);
        }

        $stats = $movieLibraryService->dashboardStats();

        $this->line(sprintf(
            'Done. %d files | %d duplicate groups | %s wasted',
            $scanLog->total_files,
            $stats['duplicate_groups'],
            MovieFile::formatBytes($stats['space_to_reclaim_bytes']),
        ));

        return self::SUCCESS;
    }
}
