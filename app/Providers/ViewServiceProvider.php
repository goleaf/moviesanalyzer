<?php

namespace App\Providers;

use App\Models\MovieFile;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $duplicateGroups = MovieFile::query()
                ->select(['tmdb_id'])
                ->matched()
                ->whereNotNull('tmdb_id')
                ->pluck('tmdb_id')
                ->countBy()
                ->filter(fn (int $count): bool => $count > 1)
                ->count();

            $view->with('navDuplicateCount', $duplicateGroups);
        });
    }
}
