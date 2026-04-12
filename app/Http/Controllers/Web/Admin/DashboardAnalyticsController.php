<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComparisonLog;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsController extends Controller
{
    public function __invoke()
    {
        $mostSearched = SearchLog::query()
            ->forQueryAggregates()
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        $noResults = SearchLog::query()
            ->forQueryAggregates()
            ->where('results_count', 0)
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        $noOffers = SearchLog::query()
            ->forQueryAggregates()
            ->where('results_count', '>', 0)
            ->where('had_offers', false)
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        $searchCounts = SearchLog::query()
            ->selectRaw('user_id, COUNT(*) as searches')
            ->groupBy('user_id')
            ->pluck('searches', 'user_id');

        $comparisonCounts = ComparisonLog::query()
            ->selectRaw('user_id, COUNT(*) as comparisons')
            ->groupBy('user_id')
            ->pluck('comparisons', 'user_id');

        $userIds = $searchCounts->keys()->merge($comparisonCounts->keys())->unique()->values();
        $usersById = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $activityByUser = $userIds
            ->map(function ($id) use ($searchCounts, $comparisonCounts, $usersById) {
                return (object) [
                    'user' => $usersById->get($id),
                    'searches' => (int) ($searchCounts[$id] ?? 0),
                    'comparisons' => (int) ($comparisonCounts[$id] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row->user !== null)
            ->sortByDesc(fn ($row) => $row->searches + $row->comparisons)
            ->values()
            ->take(20);

        return view('dashboard.analytics', compact(
            'mostSearched',
            'noResults',
            'noOffers',
            'activityByUser'
        ));
    }
}
