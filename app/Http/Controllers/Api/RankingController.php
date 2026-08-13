<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RankingService;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function __construct(private RankingService $rankingService) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'sort' => ['nullable', 'string', 'in:items,discount'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $data['sort'] ?? RankingService::SORT_ITEMS;
        $limit = (int) ($data['limit'] ?? 50);

        $rankings = $this->rankingService->rankedList($sort, $limit);

        return response()->json([
            'sort' => $sort,
            'indexed_at' => $rankings->first()?->indexed_at,
            'data' => $rankings->map(fn ($row) => [
                'supplier' => [
                    'id' => $row->supplier_id,
                    'name' => $row->supplier?->name,
                    'area' => $row->supplier?->area,
                    'phone' => $row->supplier?->phone1 ?: $row->supplier?->phone2,
                ],
                'total_items_count' => (int) $row->total_items_count,
                'discount_quality_index' => $row->discount_quality_index !== null
                    ? (float) $row->discount_quality_index
                    : null,
                'indexed_at' => $row->indexed_at,
            ])->values()->all(),
        ]);
    }
}
