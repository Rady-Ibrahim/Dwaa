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
            'price' => ['nullable', 'numeric', 'min:0'],
            'date_filter' => ['nullable', 'integer', 'in:24,48,72,168,720'],
        ]);

        $filters = [
            'price' => $data['price'] ?? null,
            'date_filter' => $data['date_filter'] ?? null,
        ];

        return response()->json(
            $this->searchService->search($request->user(), $data['q'], 1000, [], $filters)
        );
    }
}
