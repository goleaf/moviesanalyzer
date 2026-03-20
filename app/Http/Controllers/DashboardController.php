<?php

namespace App\Http\Controllers;

use App\Services\MovieLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(public MovieLibraryService $movieLibraryService) {}

    public function index(): View
    {
        return view('dashboard');
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->movieLibraryService->dashboardStats());
    }
}
