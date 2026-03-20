<?php

namespace App\Actions;

use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Models\MovieFile;
use App\Services\FilenameParser;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Log;
use Throwable;

class RescanMovieFileAction
{
    public function __construct(
        private FilenameParser $filenameParser,
        private TmdbService $tmdbService,
    ) {}

    /**
     * @return array{
     *     movie_file_id: int,
     *     status: string,
     *     parsed_clean_title: string,
     *     tmdb_id: int|null,
     *     tmdb_title: string|null,
     *     match_confidence: float|null,
     *     failed: bool,
     *     error: string|null
     * }
     */
    public function handle(MovieFile $movieFile): array
    {
        $fallbackBaseName = pathinfo($movieFile->filename, PATHINFO_FILENAME);
        $parsedFilename = new ParsedFilename(
            originalFilename: $movieFile->filename,
            baseName: $fallbackBaseName,
            cleanTitle: $fallbackBaseName,
            releaseYear: null,
            searchQueries: [$fallbackBaseName],
        );
        $match = TmdbMatch::unmatched();
        $failed = false;
        $error = null;

        try {
            $parsedFilename = $this->filenameParser->parse($movieFile->filename);
            $match = $this->tmdbService->match($parsedFilename);
        } catch (Throwable $exception) {
            $failed = true;
            $error = $exception->getMessage();

            Log::warning('Failed to rescan movie file', [
                'movie_file_id' => $movieFile->id,
                'file' => $movieFile->filename,
                'error' => $error,
            ]);
        }

        $movieFile->update([
            'parsed_base_name' => $parsedFilename->baseName,
            'parsed_clean_title' => $parsedFilename->cleanTitle,
            'parsed_search_queries' => $parsedFilename->searchQueries,
            'parsed_release_year' => $parsedFilename->releaseYear,
            'scanned_at' => now(),
            ...$match->toDatabaseAttributes(),
        ]);

        $status = $failed ? 'failed' : $match->status->value;

        return [
            'movie_file_id' => $movieFile->id,
            'status' => $status,
            'parsed_clean_title' => $parsedFilename->cleanTitle,
            'tmdb_id' => $match->tmdbId,
            'tmdb_title' => $match->title,
            'match_confidence' => $match->confidence,
            'failed' => $failed,
            'error' => $error,
        ];
    }
}
