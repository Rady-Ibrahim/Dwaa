<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchLog;
use App\Services\SearchService;
use App\Support\SafeCache;
use App\Support\SearchCache;
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

        $hasFilters = ! empty($filters['price']) || ! empty($filters['date_filter']);

        $cacheKey = 'search:v'.SearchCache::version().':'.sha1(json_encode([
            'q' => mb_strtolower(trim($data['q'])),
            'price' => $filters['price'],
            'date_filter' => $filters['date_filter'],
        ]));
        $ttl = $hasFilters ? now()->addMinutes(2) : now()->addMinutes(10);

        $searchResult = SafeCache::remember(
            $cacheKey,
            $ttl,
            fn () => $this->searchService->search(
                $request->user(),
                $data['q'],
                200,
                ['log' => false],
                $filters
            )
        );

        $results = $searchResult['results'];
        $firstProductId = $results[0]['id'] ?? null;

        SearchLog::query()->create([
            'user_id' => $request->user()->id,
            'source' => SearchLog::SOURCE_TEXT,
            'bulk_session_id' => null,
            'query' => $data['q'],
            'product_id' => $firstProductId,
            'results_count' => count($results),
            'had_offers' => collect($results)->contains(
                fn (array $product) => ! empty($product['offers'])
            ),
            'meta' => null,
        ]);

        // ── إحصائيات الموردين مجمعة حسب الخصم ─────────────────────────────
        $discountStats = [];
        foreach ($searchResult['results'] as $product) {
            foreach ($product['offers'] as $offer) {
                $discount = (float) $offer['discount'];
                $key = (string) $discount;
                if (! isset($discountStats[$key])) {
                    $discountStats[$key] = ['discount' => $discount, 'suppliers_count' => 0];
                }
                $discountStats[$key]['suppliers_count']++;
            }
        }

        usort($discountStats, fn($a, $b) => $b['discount'] <=> $a['discount']);

        $searchResult['discount_stats'] = array_values($discountStats);
        // ────────────────────────────────────────────────────────────────────

        return response()->json($searchResult);
    }
}
