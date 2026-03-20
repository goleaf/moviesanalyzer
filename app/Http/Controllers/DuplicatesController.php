<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicatesController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();

        if (! in_array($sort, ['space', 'title', 'copies'], true)) {
            $sort = 'space';
        }

        return view('duplicates', [
            'sort' => $sort,
        ]);
    }
}
