<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ComparisonLog;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\Supplier;
use App\Models\UnmatchedProduct;
use App\Models\Upload;
use App\Models\User;
use App\Services\NormalizerService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::query()->where('role', 'client')->count(),
            'suppliers' => Supplier::query()->count(),
            'pending_unmatched' => UnmatchedProduct::query()->where('status', 'pending')->count(),
            'uploads_today' => Upload::query()->whereDate('created_at', today())->count(),
        ];

        $totalSearches = SearchLog::query()->forQueryAggregates()->count();
        $totalComparisons = ComparisonLog::query()->count();

        $topQueries = SearchLog::query()
            ->forQueryAggregates()
            ->select('query', DB::raw('COUNT(*) as c'))
            ->groupBy('query')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        $maxTop = max(1, (int) ($topQueries->max('c') ?? 1));

        $searchTrend = collect(range(6, 0))->map(function (int $i) {
            $day = now()->subDays($i)->startOfDay();

            return [
                'label' => $day->format('j/n'),
                'count' => SearchLog::query()->forQueryAggregates()->whereDate('created_at', $day)->count(),
            ];
        });

        $trendMax = max(1, (int) $searchTrend->max('count'));

        return view('dashboard.index', compact(
            'stats',
            'totalSearches',
            'totalComparisons',
            'topQueries',
            'maxTop',
            'searchTrend',
            'trendMax'
        ));
    }

    public function suppliers()
    {
        return view('dashboard.suppliers', [
            'suppliers' => Supplier::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function users()
    {
        return view('dashboard.users', [
            'users' => User::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function uploads()
    {
        return view('dashboard.uploads', [
            'uploads' => Upload::query()->with('supplier')->orderByDesc('id')->paginate(15),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function products(NormalizerService $normalizer)
    {
        $search = trim((string) request('q', ''));
        $supplierId = request('supplier_id');

        $products = Product::query()
            ->with([
                'supplier',
                'offers' => function ($q) {
                    $q->orderByDesc('updated_at')->limit(1);
                },
            ])
            ->when(filled($supplierId), fn ($q) => $q->where('supplier_id', (int) $supplierId))
            ->when($search !== '', fn ($q) => $normalizer->applyFlexibleProductSearch($q, $search))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.products', [
            'products' => $products,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function mapping()
    {
        return view('dashboard.mapping', [
            'items' => UnmatchedProduct::query()
                ->with(['upload.supplier', 'resolvedProduct'])
                ->where('status', 'pending')
                ->when(request('upload_id'), fn ($q, $id) => $q->where('upload_id', $id))
                ->orderByDesc('id')
                ->paginate(15),
            'uploads' => Upload::query()->with('supplier')->orderByDesc('id')->limit(50)->get(),
        ]);
    }
}
