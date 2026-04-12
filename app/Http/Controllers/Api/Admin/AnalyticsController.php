<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComparisonLog;
use App\Models\Product;
use App\Models\SearchLog;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function mostSearched()
    {
        $rows = SearchLog::query()
            ->forQueryAggregates()
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(20)
            ->get();

        return response()->json($rows);
    }

    public function searchVolumeByUser()
    {
        $rows = SearchLog::query()
            ->selectRaw('user_id, COUNT(*) as searches')
            ->groupBy('user_id')
            ->orderByDesc('searches')
            ->limit(30)
            ->get()
            ->load(['user:id,name,phone']);

        return response()->json($rows);
    }

    /**
     * شعبية تقريبية: عدد عمليات البحث لكل product_id عندما وُجد.
     */
    public function popularProducts()
    {
        $agg = DB::table('search_logs')
            ->select('product_id', DB::raw('COUNT(*) as hits'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('hits')
            ->limit(20)
            ->get();

        $data = $agg->map(function ($row) {
            return [
                'hits' => $row->hits,
                'product' => Product::query()->find($row->product_id, ['id', 'name_ar', 'name_en', 'code']),
            ];
        });

        return response()->json($data);
    }

    public function searchesNoResults()
    {
        $rows = SearchLog::query()
            ->forQueryAggregates()
            ->where('results_count', 0)
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(30)
            ->get();

        return response()->json($rows);
    }

    public function searchesNoOffers()
    {
        $rows = SearchLog::query()
            ->forQueryAggregates()
            ->where('results_count', '>', 0)
            ->where('had_offers', false)
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(30)
            ->get();

        return response()->json($rows);
    }

    public function comparisonsByUser()
    {
        $rows = ComparisonLog::query()
            ->selectRaw('user_id, COUNT(*) as comparisons')
            ->groupBy('user_id')
            ->orderByDesc('comparisons')
            ->limit(30)
            ->get()
            ->load(['user:id,name,phone']);

        return response()->json($rows);
    }
}
