<?php

namespace App\Http\Controllers;

use App\Actions\StartScanAction;
use App\Actions\StreamScanProgressAction;
use App\Http\Requests\ScanProgressRequest;
use App\Http\Requests\StartScanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanController extends Controller
{
    public function start(StartScanRequest $request, StartScanAction $startScanAction): RedirectResponse|JsonResponse
    {
        $result = $startScanAction->handle($request->boolean('rescan_all', true));

        if ($result['already_running'] === true) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Scan is already running.'], 409);
            }

            return redirect()
                ->route('cineclean.dashboard')
                ->with('status', 'A scan is already running.');
        }

        if ($request->expectsJson()) {
            return response()->json(['queued' => true]);
        }

        return redirect()
            ->route('cineclean.dashboard')
            ->with('scan_requested', true);
    }

    public function progress(
        ScanProgressRequest $request,
        StreamScanProgressAction $streamScanProgressAction,
    ): StreamedResponse {
        return $streamScanProgressAction->handle();
    }
}
