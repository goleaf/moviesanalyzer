<?php

namespace App\Http\Controllers;

use App\Actions\RescanMovieFileAction;
use App\Actions\RescanUnmatchedMoviesAction;
use App\Enums\MatchStatus;
use App\Http\Requests\ManualMatchRequest;
use App\Http\Requests\ManualTmdbSearchRequest;
use App\Http\Requests\RescanUnmatchedRequest;
use App\Http\Requests\SkipMovieRequest;
use App\Models\MovieFile;
use App\Services\FilenameParser;
use App\Services\MovieLibraryService;
use App\Services\TmdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MoviesController extends Controller
{
    public function __construct(public MovieLibraryService $movieLibraryService) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        return view('movies.index', [
            'search' => $search,
            'movies' => $this->movieLibraryService->movieCards(
                search: $search,
                page: max(1, $request->integer('page', 1)),
                perPage: 50,
            ),
        ]);
    }

    public function unmatched(FilenameParser $filenameParser): View
    {
        $files = MovieFile::query()
            ->select([
                'id',
                'smb_path',
                'filename',
                'file_size_bytes',
                'extension',
                'parsed_clean_title',
                'parsed_release_year',
                'tmdb_id',
                'match_status',
                'match_confidence',
                'scanned_at',
            ])
            ->unmatched()
            ->orderByDesc('scanned_at')
            ->orderBy('id')
            ->get()
            ->map(function (MovieFile $movieFile) use ($filenameParser): MovieFile {
                $parsedFilename = $filenameParser->parse($movieFile->filename);
                $effectiveCleanTitle = trim($parsedFilename->cleanTitle);
                $effectiveReleaseYear = $parsedFilename->releaseYear;

                if ($effectiveCleanTitle === '') {
                    $effectiveCleanTitle = trim((string) ($movieFile->parsed_clean_title ?? ''));
                }

                if ($effectiveReleaseYear === null) {
                    $effectiveReleaseYear = $movieFile->parsed_release_year;
                }

                $movieFile->setAttribute('effective_clean_title', $effectiveCleanTitle);
                $movieFile->setAttribute('effective_release_year', $effectiveReleaseYear);

                return $movieFile;
            });

        return view('movies.unmatched', [
            'files' => $files,
        ]);
    }

    public function rescanUnmatched(
        RescanUnmatchedRequest $request,
        RescanUnmatchedMoviesAction $rescanUnmatchedMoviesAction,
    ): RedirectResponse|JsonResponse {
        $result = $rescanUnmatchedMoviesAction->handle();

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('cineclean.unmatched.index')
            ->with(
                'status',
                sprintf(
                    'Rescan complete. Processed %d files: matched %d, uncertain %d, unmatched %d, failed %d.',
                    $result['processed'],
                    $result['matched'],
                    $result['uncertain'],
                    $result['unmatched'],
                    $result['failed'],
                ),
            );
    }

    public function refreshUnmatchedMovie(
        RescanUnmatchedRequest $request,
        MovieFile $movieFile,
        RescanMovieFileAction $rescanMovieFileAction,
    ): JsonResponse {
        $result = $rescanMovieFileAction->handle($movieFile);

        return response()->json($result);
    }

    public function searchManual(
        ManualTmdbSearchRequest $request,
        MovieFile $movieFile,
        FilenameParser $filenameParser,
        TmdbService $tmdbService,
    ): JsonResponse {
        $queryInput = $request->string('query')->toString();
        $parsedSearchQuery = $filenameParser->parseSearchInput($queryInput);
        $query = $parsedSearchQuery->cleanTitle !== ''
            ? $parsedSearchQuery->cleanTitle
            : $queryInput;
        $requestYear = $request->integer('year');
        $filenameYear = $filenameParser->parse($movieFile->filename)->releaseYear
            ?? $movieFile->parsed_release_year;
        $searchYear = $requestYear > 0
            ? $requestYear
            : ($parsedSearchQuery->releaseYear ?? $filenameYear);

        return response()->json([
            'movie_file_id' => $movieFile->id,
            'normalized_query' => $query,
            'search_year' => $searchYear,
            'data' => $tmdbService->searchCandidates($query, $searchYear),
        ]);
    }

    public function applyManualMatch(
        ManualMatchRequest $request,
        MovieFile $movieFile,
        TmdbService $tmdbService,
    ): JsonResponse {
        $movie = $tmdbService->findMovieById((int) $request->integer('tmdb_id'));

        if ($movie === null) {
            return response()->json([
                'message' => 'TMDB movie not found.',
            ], 404);
        }

        $movieFile->update([
            'tmdb_id' => $movie['tmdb_id'],
            'tmdb_title' => $movie['title'],
            'tmdb_original_title' => $movie['original_title'],
            'tmdb_year' => $movie['release_year'],
            'movie_year' => $movie['release_year'],
            'tmdb_poster_path' => $movie['poster_path'],
            'tmdb_overview' => $movie['overview'],
            'tmdb_vote_average' => $movie['vote_average'],
            'tmdb_url' => $movie['tmdb_url'],
            'match_status' => MatchStatus::Matched,
            'match_confidence' => 1.0,
            'scanned_at' => now(),
        ]);

        return response()->json([
            'updated' => true,
        ]);
    }

    public function skip(SkipMovieRequest $request, MovieFile $movieFile): JsonResponse
    {
        $movieFile->update([
            'match_status' => MatchStatus::Skipped,
            'match_confidence' => null,
        ]);

        return response()->json([
            'updated' => true,
        ]);
    }
}
