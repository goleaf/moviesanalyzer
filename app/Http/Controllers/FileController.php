<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyMovieFileRequest;
use App\Models\MovieFile;
use App\Services\SmbService;
use Illuminate\Http\JsonResponse;
use Throwable;

class FileController extends Controller
{
    public function destroy(
        DestroyMovieFileRequest $request,
        MovieFile $movieFile,
        SmbService $smbService,
    ): JsonResponse {
        try {
            $smbService->deleteFile($movieFile->smb_path);
        } catch (Throwable $throwable) {
            return response()->json([
                'deleted' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $deletedId = $movieFile->id;
        $movieFile->delete();

        return response()->json([
            'deleted' => true,
            'id' => $deletedId,
        ]);
    }
}
