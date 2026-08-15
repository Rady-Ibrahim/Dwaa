<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplierRanking;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RankingController extends Controller
{
    private const REFRESH_MIN_INTERVAL_SECONDS = 60;

    private const REFRESH_LOCK_SECONDS = 120;

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

    /**
     * تحديث فوري لترتيب الموردين (مع حد أدنى بين التحديثات لمنع إساءة الاستخدام).
     */
    public function refresh(Request $request)
    {
        $lastRefresh = Cache::get('ranking:last_refresh');
        $elapsed = $lastRefresh ? now()->diffInSeconds($lastRefresh) : PHP_INT_MAX;

        if ($elapsed < self::REFRESH_MIN_INTERVAL_SECONDS) {
            return response()->json([
                'message' => 'تم التحديث منذ فترة قصيرة، حاول بعد ' . (int) ceil(self::REFRESH_MIN_INTERVAL_SECONDS - $elapsed) . ' ثانية.',
            ], 429);
        }

        $lock = Cache::lock('ranking:refresh_lock', self::REFRESH_LOCK_SECONDS);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'جاري التحديث الآن، حاول بعد قليل.',
            ], 429);
        }

        try {
            $count = $this->rankingService->recalculateAll();
            Cache::put('ranking:last_refresh', now());

            return response()->json([
                'message' => "تم تحديث الترتيب بنجاح ($count مورد).",
                'indexed_at' => SupplierRanking::query()->max('indexed_at'),
                'count' => $count,
            ]);
        } finally {
            $lock->release();
        }
    }
}
