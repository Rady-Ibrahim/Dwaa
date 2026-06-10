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

        $searchResult = $this->searchService->search($request->user(), $data['q'], 1000, [], $filters);

        // ── إحصائيات الموردين مجمعة حسب الخصم ─────────────────────────────
        // نجمع كل العروض ونحسب عدد الموردين لكل قيمة خصم بدقة كاملة
        $discountStats = [];
        foreach ($searchResult['results'] as $product) {
            foreach ($product['offers'] as $offer) {
                $discount = (float) $offer['discount'];
                // نستخدم القيمة الفعلية كـ key — بدون تقريب يخلط القيم المختلفة
                $key = (string) $discount;
                if (! isset($discountStats[$key])) {
                    $discountStats[$key] = ['discount' => $discount, 'suppliers_count' => 0];
                }
                $discountStats[$key]['suppliers_count']++;
            }
        }

        // ترتيب تنازلي حسب قيمة الخصم
        usort($discountStats, fn($a, $b) => $b['discount'] <=> $a['discount']);

        $searchResult['discount_stats'] = array_values($discountStats);
        // ────────────────────────────────────────────────────────────────────

        return response()->json($searchResult);
    }
}
