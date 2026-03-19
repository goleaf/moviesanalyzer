<?php

namespace App\Http\Controllers;

use App\Services\MovieLibraryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicatesController extends Controller
{
    public function __construct(public MovieLibraryService $movieLibraryService)
    {
    }

    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();

        if (! in_array($sort, ['space', 'title', 'copies'], true)) {
            $sort = 'space';
        }

        return view('duplicates', [
            'groups' => $this->movieLibraryService->duplicateGroups($sort),
            'sort' => $sort,
        ]);
    }
}
