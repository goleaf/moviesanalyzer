<?php

namespace App\Http\Controllers;

use App\Actions\DeleteMovieFilesAction;
use App\Http\Requests\DestroyMovieFileRequest;
use App\Models\MovieFile;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    public function destroy(
        DestroyMovieFileRequest $request,
        MovieFile $movieFile,
        DeleteMovieFilesAction $deleteMovieFilesAction,
    ): JsonResponse {
        $result = $deleteMovieFilesAction->handle([$movieFile->id]);
        $firstResult = $result['results'][0] ?? null;

        if (! is_array($firstResult) || (($firstResult['success'] ?? false) !== true)) {
            return response()->json([
                'deleted' => false,
                'message' => is_array($firstResult) && is_string($firstResult['message'] ?? null)
                    ? $firstResult['message']
                    : 'Movie file deletion failed.',
            ], 422);
        }

        $deletedId = $movieFile->id;

        return response()->json([
            'deleted' => true,
            'id' => $deletedId,
        ]);
    }
}
