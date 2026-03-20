<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FilenameRulesController;
use App\Http\Controllers\MoviesController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('/')
    ->name('cineclean.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/scan/start', [ScanController::class, 'start'])->name('scan.start');
        Route::get('/scan/progress', [ScanController::class, 'progress'])->name('scan.progress');

        Route::get('/movies', [MoviesController::class, 'index'])->name('movies.index');
        Route::get('/movies/{movieFile}', [MoviesController::class, 'show'])->name('movies.show');
        Route::post('/movies/{movieFile}/sync-details', [MoviesController::class, 'syncDetails'])->name('movies.sync-details');
        Route::get('/duplicates', [DuplicatesController::class, 'index'])->name('duplicates.index');
        Route::get('/unmatched', [MoviesController::class, 'unmatched'])->name('unmatched.index');
        Route::post('/unmatched/rescan', [MoviesController::class, 'rescanUnmatched'])->name('unmatched.rescan');
        Route::get('/parser-rules', [FilenameRulesController::class, 'index'])->name('rules.index');
        Route::post('/parser-rules', [FilenameRulesController::class, 'store'])->name('rules.store');
        Route::patch('/parser-rules/{filenameRule}', [FilenameRulesController::class, 'update'])->name('rules.update');
        Route::delete('/parser-rules/{filenameRule}', [FilenameRulesController::class, 'destroy'])->name('rules.destroy');
        Route::post('/parser-rules/preview', [FilenameRulesController::class, 'preview'])->name('rules.preview');

        Route::post('/unmatched/{movieFile}/search', [MoviesController::class, 'searchManual'])->name('unmatched.search');
        Route::post('/unmatched/{movieFile}/refresh', [MoviesController::class, 'refreshUnmatchedMovie'])->name('unmatched.refresh');
        Route::patch('/unmatched/{movieFile}/match', [MoviesController::class, 'applyManualMatch'])->name('unmatched.match');
        Route::patch('/unmatched/{movieFile}/skip', [MoviesController::class, 'skip'])->name('unmatched.skip');

        Route::delete('/file/{movieFile}', [FileController::class, 'destroy'])->name('file.destroy');
        Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
    });
