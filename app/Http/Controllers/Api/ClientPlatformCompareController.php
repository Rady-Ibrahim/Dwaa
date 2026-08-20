<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Upload;
use App\Services\ExcelSearchService;
use App\Services\NormalizerService;
use App\Services\PlatformCompareService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClientPlatformCompareController extends Controller
{
    private const MAX_ROWS = 1000;

    private const MIN_NAME_OVERLAP = 60.0;

    private const PRICE_EQUAL_RATIO = 0.97;

    private const PRICE_CLOSE_RATIO = 0.80;

    private const PRICE_FAR_PENALTY = 0.3;

    private array $namePrepareCache = [];

    private static ?array $stopWordMap = null;

    public function __construct(
        private ExcelSearchService $excelSearchService,
        private NormalizerService $normalizer,
        private PlatformCompareService $platformCompareService,
    ) {}

    /**
     * الوضع 1: مقارنة ملف يرفعه العميل مع المنصة ككل (المسارات النشطة).
     */
    public function __invoke(Request $request)
    {
        $startMs = microtime(true);

        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        Log::info('PlatformCompare: START', [
            'user_id' => $request->user()?->id,
            'file_size' => $request->file('file')?->getSize(),
            'file_mime' => $request->file('file')?->getMimeType(),
            'file_name' => $request->file('file')?->getClientOriginalName(),
        ]);

        try {
            $rows = $this->readRows($request);
        } catch (\Throwable $e) {
            Log::error('PlatformCompare: readRows FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        Log::info('PlatformCompare: readRows done', [
            'rows_count' => count($rows),
            'elapsed_ms' => round((microtime(true) - $startMs) * 1000),
        ]);

        if (empty($rows)) {
            Log::warning('PlatformCompare: empty rows, aborting', [
                'elapsed_ms' => round((microtime(true) - $startMs) * 1000),
            ]);
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $cacheStart = microtime(true);

        try {
            $cache = $this->loadAllPlatformCache($rows);
        } catch (\Throwable $e) {
            Log::error('PlatformCompare: loadAllPlatformCache FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'rows_count' => count($rows),
            ]);
            throw $e;
        }

        Log::info('PlatformCompare: cache loaded', [
            'products_loaded' => count($cache),
            'elapsed_ms' => round((microtime(true) - $cacheStart) * 1000),
        ]);

        $buildStart = microtime(true);

        try {
            $lines = $this->platformCompareService->buildLines($rows, $cache);
        } catch (\Throwable $e) {
            Log::error('PlatformCompare: buildLines FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'rows_count' => count($rows),
                'products_count' => count($cache),
            ]);
            throw $e;
        }

        $lines = $this->platformCompareService->sortLines($lines);

        Log::info('PlatformCompare: DONE', [
            'rows_count' => count($rows),
            'products_count' => count($cache),
            'lines_count' => count($lines),
            'total_elapsed_ms' => round((microtime(true) - $startMs) * 1000),
            'build_elapsed_ms' => round((microtime(true) - $buildStart) * 1000),
        ]);

        return response()->json([
            'rows_read' => count($rows),
            'lines' => $lines,
        ]);
    }

    /**
     * قائمة الملفات المرفوعة من الأدمن (لاختيارها في أوضاع المقارنة).
     */
    public function uploads(Request $request)
    {
        $uploads = Upload::query()
            ->with('supplier:id,name')
            ->whereHas('offers')
            ->orderByDesc('created_at')
            ->get(['id', 'supplier_id', 'file_path', 'status', 'matched_count', 'created_at']);

        return response()->json([
            'uploads' => $uploads->map(function (Upload $upload) {
                return [
                    'id' => $upload->id,
                    'file_name' => $upload->original_name,
                    'supplier' => $upload->supplier?->name,
                    'status' => $upload->status,
                    'matched_count' => $upload->matched_count,
                    'created_at' => $upload->created_at?->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    /**
     * الوضع 2: مقارنة ملف يرفعه العميل مع ملف واحد محدد من الملفات المرفوعة من الأدمن.
     */
    public function fileToUpload(Request $request)
    {
        $startMs = microtime(true);

        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'upload_id' => ['required', 'exists:uploads,id'],
        ]);

        $rows = $this->readRows($request);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $uploadId = (int) $data['upload_id'];

        $cacheStart = microtime(true);

        $cache = $this->loadUploadCache($uploadId);

        Log::info('PlatformCompare: fileToUpload cache loaded', [
            'upload_id' => $uploadId,
            'products_loaded' => count($cache),
            'elapsed_ms' => round((microtime(true) - $cacheStart) * 1000),
        ]);

        $buildStart = microtime(true);

        $lines = $this->platformCompareService->sortLines(
            $this->platformCompareService->buildLines($rows, $cache, $uploadId),
        );

        Log::info('PlatformCompare: fileToUpload DONE', [
            'upload_id' => $uploadId,
            'rows_count' => count($rows),
            'lines_count' => count($lines),
            'total_elapsed_ms' => round((microtime(true) - $startMs) * 1000),
            'build_elapsed_ms' => round((microtime(true) - $buildStart) * 1000),
        ]);

        return response()->json([
            'rows_read' => count($rows),
            'lines' => $lines,
        ]);
    }

    /**
     * الوضع 3: مقارنة ملفين من الملفات المرفوعة من الأدمن مع بعضهما.
     */
    public function uploadsCompare(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $data = $request->validate([
            'upload_id_a' => ['required', 'exists:uploads,id'],
            'upload_id_b' => ['required', 'exists:uploads,id', 'different:upload_id_a'],
        ]);

        $socketB = Upload::with('supplier:id,name')->findOrFail($data['upload_id_b']);

        $entriesA = $this->buildUploadsIndex((int) $data['upload_id_a']);
        $entriesB = $this->buildUploadsIndex((int) $data['upload_id_b']);

        $lines = [];
        $usedB = [];
        $usedA = [];

        foreach ($entriesA as $name => $entryA) {
            if (! isset($entriesB[$name])) {
                continue;
            }

            $priceA = $entryA['offer']->price;
            $priceB = $entriesB[$name]['offer']->price;

            $priceScore = $this->applyPriceScore(
                $priceA !== null ? (float) $priceA : null,
                $priceB !== null ? (float) $priceB : null,
                100.0,
            );

            if ($priceScore < 100.0) {
                continue;
            }

            $usedA[$name] = true;
            $usedB[$name] = true;

            $lines[] = $this->buildPairLine($entryA, $entriesB[$name], $socketB, 100.0);
        }

        $preparedA = [];
        foreach ($entriesA as $nameA => $entryA) {
            $preparedA[$nameA] = $this->prepareName((string) $nameA);
        }

        $preparedB = [];
        $bByFirstToken = [];
        foreach ($entriesB as $nameB => $entryB) {
            $preparedB[$nameB] = $this->prepareName((string) $nameB);

            $firstToken = $preparedB[$nameB]['tokens'][0] ?? '';
            if ($firstToken !== '') {
                $bByFirstToken[$firstToken][] = $nameB;
            }
        }

        foreach ($entriesA as $nameA => $entryA) {
            if (isset($usedA[$nameA])) {
                continue;
            }

            $bestKey = null;
            $bestScore = 0.0;

            $prepA = $preparedA[$nameA];
            $candidateNames = isset($prepA['tokens'][0])
                ? ($bByFirstToken[$prepA['tokens'][0]] ?? [])
                : [];

            foreach ($candidateNames as $nameB) {
                if (isset($usedB[$nameB])) {
                    continue;
                }

                $entryB = $entriesB[$nameB];

                $priceA = $entryA['offer']->price;
                $priceB = $entryB['offer']->price;

                $prepB = $preparedB[$nameB];

                $score = $this->applyPriceScore(
                    $priceA !== null ? (float) $priceA : null,
                    $priceB !== null ? (float) $priceB : null,
                    $this->drugOverlapScorePrepared(
                        $prepA['tokens'],
                        $prepB['tokens'],
                        $prepA['numbers'],
                        $prepB['numbers'],
                    ),
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestKey = $nameB;
                }
            }

            if ($bestKey === null || $bestScore < self::MIN_NAME_OVERLAP) {
                $lines[] = $this->buildOnlyALine($entryA);

                continue;
            }

            $usedB[$bestKey] = true;
            $lines[] = $this->buildPairLine($entryA, $entriesB[$bestKey], $socketB, round($bestScore, 1));
        }

        foreach ($entriesB as $nameB => $entryB) {
            if (isset($usedB[$nameB])) {
                continue;
            }

            $lines[] = $this->buildOnlyBLine($entryB, $socketB);
        }

        $lines = $this->sortLinesMode3($lines);

        return response()->json([
            'rows_read' => count($entriesA) + count($entriesB),
            'lines' => $lines,
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    //  Mode 3 helpers (uploadsCompare)
    // ════════════════════════════════════════════════════════════════

    private function buildUploadsIndex(int $uploadId): array
    {
        $bestOffers = $this->bestOffersByProduct($uploadId);

        $products = Product::query()
            ->whereIn('id', $bestOffers->keys()->all())
            ->get(['id', 'name_ar', 'name_en', 'normalized_name'])
            ->keyBy('id');

        $index = [];

        foreach ($bestOffers as $productId => $offer) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $key = trim((string) $product->normalized_name);

            if ($key === '') {
                continue;
            }

            $existing = $index[$key] ?? null;

            if (! $existing || (float) $offer->price < (float) $existing['offer']->price) {
                $index[$key] = [
                    'product' => $product,
                    'offer' => $offer,
                ];
            }
        }

        return $index;
    }

    private function bestOffersByProduct(int $uploadId): Collection
    {
        return Offer::query()
            ->where('upload_id', $uploadId)
            ->with('supplier:id,name,area,phone1,phone2')
            ->orderBy('discount', 'desc')
            ->orderBy('price')
            ->get()
            ->groupBy('product_id')
            ->map->first();
    }

    private function buildPairLine(array $entryA, array $entryB, Upload $socketB, float $similarity): array
    {
        $productA = $entryA['product'];
        $productB = $entryB['product'];
        $offerA = $entryA['offer'];
        $offerB = $entryB['offer'];

        $nameA = $productA->name_ar ?: $productA->name_en;
        $nameB = $productB->name_ar ?: $productB->name_en;

        $priceA = $offerA->price !== null ? (float) $offerA->price : null;
        $priceB = $offerB->price !== null ? (float) $offerB->price : null;

        return [
            'query' => $nameA,
            'price' => $priceA,
            'discount' => $offerA->discount !== null ? (float) $offerA->discount : null,
            'matched_product' => $nameB,
            'similarity' => $similarity,
            'platform_best' => [
                'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                'price' => $priceB,
                'discount' => $offerB->discount !== null ? (float) $offerB->discount : null,
            ],
            'status' => 'both',
        ];
    }

    private function buildOnlyALine(array $entryA): array
    {
        $productA = $entryA['product'];
        $offerA = $entryA['offer'];

        $nameA = $productA->name_ar ?: $productA->name_en;

        return [
            'query' => $nameA,
            'price' => $offerA->price !== null ? (float) $offerA->price : null,
            'discount' => $offerA->discount !== null ? (float) $offerA->discount : null,
            'matched_product' => $nameA,
            'similarity' => 0.0,
            'platform_best' => null,
            'status' => 'only_a',
        ];
    }

    private function buildOnlyBLine(array $entryB, Upload $socketB): array
    {
        $productB = $entryB['product'];
        $offerB = $entryB['offer'];

        $nameB = $productB->name_ar ?: $productB->name_en;

        return [
            'query' => $nameB,
            'price' => null,
            'discount' => null,
            'matched_product' => $nameB,
            'similarity' => 0.0,
            'platform_best' => [
                'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                'price' => $offerB->price !== null ? (float) $offerB->price : null,
                'discount' => $offerB->discount !== null ? (float) $offerB->discount : null,
            ],
            'status' => 'only_b',
        ];
    }

    // ════════════════════════════════════════════════════════════════
    //  Text Analysis Helpers (shared by mode 3)
    // ════════════════════════════════════════════════════════════════

    private function prepareName(string $name): array
    {
        return $this->namePrepareCache[$name] ??= [
            'tokens' => $this->contentTokens($name),
            'numbers' => $this->contentNumbers($name),
        ];
    }

    private function contentTokens(string $name): array
    {
        preg_match_all('/\p{L}+/u', $name, $matches);

        $out = [];

        foreach ($matches[0] as $run) {
            $lower = mb_strtolower($run);
            $lower = str_replace(
                ['أ', 'إ', 'آ', 'ٱ', 'ى', 'ئ', 'ة', 'ؤ', 'ء'],
                ['ا', 'ا', 'ا', 'ا', 'ي', 'ي', 'ه', 'و', ''],
                $lower
            );

            if (mb_strlen($lower) < 2) {
                continue;
            }

            if (isset(self::stopWordMap()[$lower])) {
                continue;
            }

            $out[] = $lower;
        }

        return $out;
    }

    private static function stopWordMap(): array
    {
        return self::$stopWordMap ??= array_flip(self::DRUG_STOP_WORDS());
    }

    private static function DRUG_STOP_WORDS(): array
    {
        return [
            'اقراص', 'قرص', 'كبسول', 'كبسوله', 'كبسولات', 'حبوب', 'شراب', 'حقن',
            'قطرات', 'قطره', 'نقط', 'مرهم', 'كريم', 'جل', 'بخاخ', 'سبراي', 'محلول',
            'معلق', 'لبوس', 'تحاميل', 'امبول', 'امبولات', 'شريط', 'شرايط', 'باكت',
            'علبه', 'زجاجه', 'مسحوق', 'بودره', 'سيروم', 'لوشن', 'لوسيون', 'مجم',
            'ملجم', 'مليجرام', 'جرام', 'ملى', 'مللى', 'مل', 'سم', 'لتر', 'كجم',
            'فموي', 'وريدي', 'موضعي', 'مستمر', 'معجل', 'مؤخر', 'سعر', 'جنيه', 'جنية',
            'مع', 'وزن', 'عبوه', 'محيط', 'العين', 'مطهر', 'غسول', 'جديد', 'احمر',
            'اكسترا', 'ال', 'جيل', 'كبير', 'صغير', 'كبيره', 'صغيره', 'صن', 'بلوك',
            'بديل', 'سيرم', 'شامبو', 'مضاد', 'حيوي', 'ادويه', 'علاج', 'دواء', 'مستحضر',
            'كبسولات',
        ];
    }

    private function contentNumbers(string $name): array
    {
        $text = str_replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $name);

        preg_match_all('/\d+(?:\.\d+)?/u', $text, $matches);

        return $matches[0];
    }

    private function drugOverlapScorePrepared(
        array $tokensA,
        array $tokensB,
        array $numbersA,
        array $numbersB,
    ): float {
        if (! empty($numbersA) && ! empty($numbersB)) {
            if (array_intersect($numbersA, $numbersB) === []) {
                return 0.0;
            }

            if (($numbersA[0] ?? null) !== ($numbersB[0] ?? null)) {
                return 0.0;
            }
        }

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        if (($tokensA[0] ?? '') !== ($tokensB[0] ?? '')) {
            return 0.0;
        }

        if (isset($tokensA[1], $tokensB[1]) && $tokensA[1] !== $tokensB[1]) {
            return 0.0;
        }

        $shared = [];

        foreach ($tokensA as $tokenA) {
            if (in_array($tokenA, $tokensB, true)) {
                $shared[] = $tokenA;
            }
        }

        if (empty($shared)) {
            return 0.0;
        }

        $longestShared = max(array_map('mb_strlen', $shared));

        if ($longestShared < 4) {
            return 0.0;
        }

        return round((count($shared) / max(count($tokensA), count($tokensB))) * 100, 1);
    }

    private function applyPriceScore(?float $sheetPrice, ?float $productPrice, float $nameScore): float
    {
        if ($sheetPrice === null || $productPrice === null || $sheetPrice <= 0 || $productPrice <= 0) {
            return $nameScore;
        }

        $ratio = min($sheetPrice, $productPrice) / max($sheetPrice, $productPrice);

        if ($ratio >= self::PRICE_EQUAL_RATIO) {
            return min(100.0, $nameScore + 10);
        }

        if ($ratio < self::PRICE_CLOSE_RATIO) {
            return $nameScore * self::PRICE_FAR_PENALTY;
        }

        if ($nameScore < 100.0) {
            return $nameScore * 0.6;
        }

        return $nameScore;
    }

    private function sortLinesMode3(array $lines): array
    {
        usort($lines, function (array $a, array $b): int {
            $aMatch = (int) (($a['similarity'] ?? 0) > 0);
            $bMatch = (int) (($b['similarity'] ?? 0) > 0);
            if ($aMatch !== $bMatch) {
                return $bMatch <=> $aMatch;
            }
            if (($a['status'] ?? '') === 'only_b') {
                return 1;
            }
            if (($b['status'] ?? '') === 'only_b') {
                return -1;
            }
            $aOffer = (int) (! empty($a['platform_best']['supplier']));
            $bOffer = (int) (! empty($b['platform_best']['supplier']));
            if ($aOffer !== $bOffer) {
                return $bOffer <=> $aOffer;
            }

            return strcmp((string) ($a['query'] ?? ''), (string) ($b['query'] ?? ''));
        });

        return $lines;
    }

    // ════════════════════════════════════════════════════════════════
    //  Data Loading Helpers (modes 1, 2)
    // ════════════════════════════════════════════════════════════════

    private function readRows(Request $request): array
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $path = $request->file('file')->store('temp/compare-platform/'.now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);

        Log::info('PlatformCompare: readRows - file stored', [
            'path' => $path,
            'fullPath' => $fullPath,
            'exists' => file_exists($fullPath),
            'size' => @filesize($fullPath),
        ]);

        try {
            $rows = $this->excelSearchService->readRowsAutoForPlatformCompare($fullPath, self::MAX_ROWS);

            Log::info('PlatformCompare: readRows - parsed', [
                'rows_count' => count($rows),
                'first_row' => $rows[0] ?? null,
            ]);

            return $rows;
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * تحميل جميع منتجات المنصة النشطة مع أفضل عرض لكل منتج.
     *
     * يُخزّن كـ plain associative arrays + precomputed tokens/numbers
     * لتقليل حجم الكاش وتسريع البناء.
     *
     * @return array<int, array{id:int, name_ar:string|null, name_en:string|null, code:string|null, normalized_name:string|null, tokens:array, numbers:array, ar_tokens:array, ar_numbers:array, en_tokens:array, en_numbers:array, best_offer: array{id:int, price:float, discount:float, supplier_name:string|null}|null}>
     */
    private function loadAllPlatformCache(array $rows): array
    {
        $startMs = microtime(true);

        $cacheKey = 'platform_compare_products_cache_v3';

        $products = Cache::remember($cacheKey, 12 * 60 * 60, function (): array {
            $rows = DB::table('products')
                ->join('offers', function ($join) {
                    $join->on('offers.product_id', '=', 'products.id')
                        ->where(function ($q) {
                            $q->where('expires_at', '>', now())
                                ->orWhereNull('expires_at');
                        });
                })
                ->leftJoin('suppliers', 'suppliers.id', '=', 'offers.supplier_id')
                ->select([
                    'products.id',
                    'products.name_ar',
                    'products.name_en',
                    'products.code',
                    'products.normalized_name',
                    'offers.id as offer_id',
                    'offers.price as offer_price',
                    'offers.discount as offer_discount',
                    'suppliers.name as supplier_name',
                ])
                ->orderBy('products.id')
                ->orderBy('offers.discount', 'desc')
                ->orderBy('offers.price')
                ->get();

            $productsById = [];

            foreach ($rows as $row) {
                $pid = (int) $row->id;

                if (! isset($productsById[$pid])) {
                    $normName = (string) ($row->normalized_name ?? '');
                    $nameAr = (string) ($row->name_ar ?? '');
                    $nameEn = (string) ($row->name_en ?? '');

                    $productsById[$pid] = [
                        'id' => $pid,
                        'name_ar' => $row->name_ar,
                        'name_en' => $row->name_en,
                        'code' => $row->code,
                        'normalized_name' => $row->normalized_name,
                        'tokens' => $this->computeContentTokens($normName),
                        'numbers' => $this->computeContentNumbers($normName),
                        'ar_tokens' => $nameAr !== '' ? $this->computeContentTokens($this->normalizeCacheText($nameAr)) : [],
                        'ar_numbers' => $nameAr !== '' ? $this->computeContentNumbers($this->normalizeCacheText($nameAr)) : [],
                        'en_tokens' => $nameEn !== '' ? $this->computeContentTokens($nameEn) : [],
                        'en_numbers' => $nameEn !== '' ? $this->computeContentNumbers($nameEn) : [],
                        'best_offer' => [
                            'id' => (int) $row->offer_id,
                            'price' => (float) $row->offer_price,
                            'discount' => (float) $row->offer_discount,
                            'supplier_name' => $row->supplier_name,
                        ],
                    ];
                }
            }

            return array_values($productsById);
        });

        Log::info('PlatformCompare: loadAllPlatformCache done', [
            'products_loaded' => count($products),
            'elapsed_ms' => round((microtime(true) - $startMs) * 1000),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 1),
        ]);

        return $products;
    }

    /**
     * تحميل منتجات ملف مرفوع مع أفضل عرض لكل منتج (وضع 2).
     *
     * @return array<int, array{id:int, name_ar:string|null, name_en:string|null, code:string|null, normalized_name:string|null, tokens:array, numbers:array, ar_tokens:array, ar_numbers:array, en_tokens:array, en_numbers:array, best_offer: array{id:int, price:float, discount:float, supplier_name:string|null}|null}>
     */
    private function loadUploadCache(int $uploadId): array
    {
        $startMs = microtime(true);

        $cacheKey = "upload_compare_cache_v3_{$uploadId}";

        $products = Cache::remember($cacheKey, 60 * 60, function () use ($uploadId): array {
            $rows = DB::table('products')
                ->join('offers', function ($join) use ($uploadId) {
                    $join->on('offers.product_id', '=', 'products.id')
                        ->where('offers.upload_id', '=', $uploadId);
                })
                ->leftJoin('suppliers', 'suppliers.id', '=', 'offers.supplier_id')
                ->select([
                    'products.id',
                    'products.name_ar',
                    'products.name_en',
                    'products.code',
                    'products.normalized_name',
                    'offers.id as offer_id',
                    'offers.price as offer_price',
                    'offers.discount as offer_discount',
                    'suppliers.name as supplier_name',
                ])
                ->orderBy('products.id')
                ->orderBy('offers.discount', 'desc')
                ->orderBy('offers.price')
                ->get();

            $productsById = [];

            foreach ($rows as $row) {
                $pid = (int) $row->id;

                if (! isset($productsById[$pid])) {
                    $normName = (string) ($row->normalized_name ?? '');
                    $nameAr = (string) ($row->name_ar ?? '');
                    $nameEn = (string) ($row->name_en ?? '');

                    $productsById[$pid] = [
                        'id' => $pid,
                        'name_ar' => $row->name_ar,
                        'name_en' => $row->name_en,
                        'code' => $row->code,
                        'normalized_name' => $row->normalized_name,
                        'tokens' => $this->computeContentTokens($normName),
                        'numbers' => $this->computeContentNumbers($normName),
                        'ar_tokens' => $nameAr !== '' ? $this->computeContentTokens($this->normalizeCacheText($nameAr)) : [],
                        'ar_numbers' => $nameAr !== '' ? $this->computeContentNumbers($this->normalizeCacheText($nameAr)) : [],
                        'en_tokens' => $nameEn !== '' ? $this->computeContentTokens($nameEn) : [],
                        'en_numbers' => $nameEn !== '' ? $this->computeContentNumbers($nameEn) : [],
                        'best_offer' => [
                            'id' => (int) $row->offer_id,
                            'price' => (float) $row->offer_price,
                            'discount' => (float) $row->offer_discount,
                            'supplier_name' => $row->supplier_name,
                        ],
                    ];
                }
            }

            return array_values($productsById);
        });

        Log::info('PlatformCompare: loadUploadCache done', [
            'upload_id' => $uploadId,
            'products_loaded' => count($products),
            'elapsed_ms' => round((microtime(true) - $startMs) * 1000),
        ]);

        return $products;
    }

    /**
     * استخراج Content Tokens من نص (مطابق لمنطق Service).
     */
    private function computeContentTokens(string $name): array
    {
        preg_match_all('/\p{L}+/u', $name, $matches);

        $stopWords = array_flip([
            'اقراص', 'قرص', 'كبسول', 'كبسوله', 'كبسولات', 'حبوب', 'شراب', 'حقن',
            'قطرات', 'قطره', 'نقط', 'مرهم', 'كريم', 'جل', 'بخاخ', 'سبراي', 'محلول',
            'معلق', 'لبوس', 'تحاميل', 'امبول', 'امبولات', 'شريط', 'شرايط', 'باكت',
            'علبه', 'زجاجه', 'مسحوق', 'بودره', 'سيروم', 'لوشن', 'لوسيون', 'مجم',
            'ملجم', 'مليجرام', 'جرام', 'ملى', 'مللى', 'مل', 'سم', 'لتر', 'كجم',
            'فموي', 'وريدي', 'موضعي', 'مستمر', 'معجل', 'مؤخر', 'سعر', 'جنيه', 'جنية',
            'مع', 'وزن', 'عبوه', 'محيط', 'العين', 'مطهر', 'غسول', 'جديد', 'احمر',
            'اكسترا', 'ال', 'جيل', 'كبير', 'صغير', 'كبيره', 'صغيره', 'صن', 'بلوك',
            'بديل', 'سيرم', 'شامبو', 'مضاد', 'حيوي', 'ادويه', 'علاج', 'دواء', 'مستحضر',
            'كبسولات',
        ]);

        $out = [];
        foreach ($matches[0] as $run) {
            $lower = mb_strtolower($run);
            $lower = str_replace(
                ['أ', 'إ', 'آ', 'ٱ', 'ى', 'ئ', 'ة', 'ؤ', 'ء'],
                ['ا', 'ا', 'ا', 'ا', 'ي', 'ي', 'ه', 'و', ''],
                $lower,
            );

            if (mb_strlen($lower) < 2) {
                continue;
            }
            if (isset($stopWords[$lower])) {
                continue;
            }

            $out[] = $lower;
        }

        return $out;
    }

    private function computeContentNumbers(string $name): array
    {
        $text = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $name,
        );

        preg_match_all('/\d+(?:\.\d+)?/u', $text, $matches);

        return $matches[0];
    }

    private function normalizeCacheText(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $normalized);
        $normalized = str_replace(['ى', 'ئ', 'ة', 'ؤ', 'ء'], ['ي', 'ي', 'ه', 'و', ''], $normalized);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
