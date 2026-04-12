<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        return response()->json(
            $this->searchService->search($request->user(), $data['q'])
        );
    }
}
