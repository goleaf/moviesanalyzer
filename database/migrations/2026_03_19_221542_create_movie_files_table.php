<?php

use App\Enums\MatchStatus;
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
        Schema::create('movie_files', function (Blueprint $table): void {
            $table->id();
            $table->string('smb_path', 1024)->unique();
            $table->string('filename');
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('extension', 20);
            $table->unsignedBigInteger('tmdb_id')->nullable()->index();
            $table->string('tmdb_title')->nullable();
            $table->string('tmdb_original_title')->nullable();
            $table->unsignedSmallInteger('tmdb_year')->nullable();
            $table->string('tmdb_poster_path')->nullable();
            $table->text('tmdb_overview')->nullable();
            $table->float('tmdb_vote_average')->nullable();
            $table->string('tmdb_url')->nullable();
            $table->enum('match_status', array_map(
                static fn (MatchStatus $status): string => $status->value,
                MatchStatus::cases(),
            ))->default(MatchStatus::Unmatched->value)->index();
            $table->float('match_confidence')->nullable();
            $table->timestamp('scanned_at')->nullable()->index();
            $table->timestamps();

            $table->index(['extension', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_files');
    }
};
