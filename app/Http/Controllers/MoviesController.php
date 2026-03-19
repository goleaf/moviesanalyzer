<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Http\Requests\ManualMatchRequest;
use App\Http\Requests\ManualTmdbSearchRequest;
use App\Http\Requests\SkipMovieRequest;
use App\Models\MovieFile;
use App\Services\MovieLibraryService;
use App\Services\TmdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MoviesController extends Controller
{
    public function __construct(public MovieLibraryService $movieLibraryService)
    {
    }

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

    public function unmatched(): View
    {
        $files = MovieFile::query()
            ->select([
                'id',
                'smb_path',
                'filename',
                'file_size_bytes',
                'extension',
                'tmdb_id',
                'match_status',
                'match_confidence',
                'scanned_at',
            ])
            ->unmatched()
            ->orderByDesc('scanned_at')
            ->paginate(50)
            ->withQueryString();

        return view('movies.unmatched', [
            'files' => $files,
        ]);
    }

    public function searchManual(
        ManualTmdbSearchRequest $request,
        MovieFile $movieFile,
        TmdbService $tmdbService,
    ): JsonResponse {
        $query = $request->string('query')->toString();

        return response()->json([
            'movie_file_id' => $movieFile->id,
            'data' => $tmdbService->searchCandidates($query),
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
