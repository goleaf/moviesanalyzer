<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movie_files', function (Blueprint $table): void {
            $table->string('tmdb_original_language')->nullable()->after('tmdb_original_title');
            $table->float('tmdb_popularity')->nullable()->after('tmdb_vote_average');
            $table->unsignedInteger('tmdb_vote_count')->nullable()->after('tmdb_popularity');
            $table->unsignedSmallInteger('tmdb_runtime')->nullable()->after('movie_year');
            $table->date('tmdb_release_date')->nullable()->after('tmdb_runtime');
            $table->string('tmdb_tagline')->nullable()->after('tmdb_release_date');
            $table->string('tmdb_status')->nullable()->after('tmdb_tagline');
            $table->string('tmdb_imdb_id')->nullable()->after('tmdb_status');
            $table->json('tmdb_metadata')->nullable()->after('tmdb_imdb_id');

            $table->index('tmdb_original_language');
            $table->index('tmdb_release_date');
        });
    }

    public function down(): void
    {
        Schema::table('movie_files', function (Blueprint $table): void {
            $table->dropIndex(['tmdb_original_language']);
            $table->dropIndex(['tmdb_release_date']);

            $table->dropColumn([
                'tmdb_original_language',
                'tmdb_popularity',
                'tmdb_vote_count',
                'tmdb_runtime',
                'tmdb_release_date',
                'tmdb_tagline',
                'tmdb_status',
                'tmdb_imdb_id',
                'tmdb_metadata',
            ]);
        });
    }
};
