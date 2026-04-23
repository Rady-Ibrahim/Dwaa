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
use App\Models\Advertisement;
use App\Models\Setting;
use App\Services\NormalizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

    public function settings()
    {
        $advertisements = Advertisement::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $generalSettings = [
            'app_name' => Setting::get('app_name', 'Med RANKO'),
            'app_description' => Setting::get('app_description', 'رتّب صح ووفر أكتر'),
            'support_email' => Setting::get('support_email', 'support@medranko.com'),
            'support_phone' => Setting::get('support_phone', '+20 123 456 7890'),
            'ticker_enabled' => Setting::get('ticker_enabled', '1') === '1',
            'ticker_speed' => Setting::get('ticker_speed', '20'),
        ];

        return view('dashboard.settings', compact('advertisements', 'generalSettings'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->all();
        
        // Handle boolean fields properly
        $data['ticker_enabled'] = isset($data['ticker_enabled']) ? true : false;
        
        $request->validate([
            'advertisements' => 'array',
            'advertisements.*' => 'nullable|string|max:255',
            'ticker_enabled' => 'boolean',
            'ticker_speed' => 'integer|min:5|max:60',
            'app_name' => 'string|max:255',
            'app_description' => 'string|max:500',
            'support_email' => 'email|max:255',
            'support_phone' => 'string|max:255',
        ]);

        // Clear existing advertisements
        Advertisement::query()->delete();

        // Create new advertisements
        if (!empty($request->advertisements)) {
            foreach ($request->advertisements as $index => $message) {
                if (!empty(trim($message ?? ''))) {
                    Advertisement::create([
                        'message' => trim($message),
                        'is_active' => $data['ticker_enabled'],
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        // Save general settings
        Setting::set('app_name', $request->app_name);
        Setting::set('app_description', $request->app_description);
        Setting::set('support_email', $request->support_email);
        Setting::set('support_phone', $request->support_phone);
        Setting::set('ticker_enabled', $data['ticker_enabled'] ? '1' : '0');
        Setting::set('ticker_speed', $request->ticker_speed);

        return redirect()->route('dashboard.settings')
            ->with('status', 'تم حفظ الإعدادات بنجاح');
    }
}
