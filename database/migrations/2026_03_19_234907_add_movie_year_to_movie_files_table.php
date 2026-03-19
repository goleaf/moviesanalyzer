<?php

use App\Models\MovieFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movie_files', function (Blueprint $table) {
            $table->unsignedSmallInteger('movie_year')->nullable()->index();
        });

        MovieFile::query()
            ->select(['id', 'tmdb_year'])
            ->whereNotNull('tmdb_year')
            ->orderBy('id')
            ->lazyById(200)
            ->each(function (MovieFile $movieFile): void {
                MovieFile::query()
                    ->whereKey($movieFile->id)
                    ->update([
                        'movie_year' => $movieFile->tmdb_year,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_files', function (Blueprint $table) {
            $table->dropColumn('movie_year');
        });
    }
};
