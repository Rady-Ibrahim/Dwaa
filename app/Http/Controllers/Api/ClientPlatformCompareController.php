<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Upload;
use App\Services\ExcelSearchService;
use App\Services\NormalizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClientPlatformCompareController extends Controller
{
    /** أقصى عدد صفوف تُقرأ من الملف */
    private const MAX_ROWS = 1000;

    /** الحد الأدنى لنسبة التشابه للقبول */
    private const MIN_SIMILARITY = 45.0;

    /** حجم الـ chunk لمعالجة المنتجات على دفعات */
    private const CHUNK_KEYWORDS = 50;

    public function __construct(
        private ExcelSearchService $excelSearchService,
        private NormalizerService  $normalizer,
    ) {}

    /**
     * الوضع 1: مقارنة ملف يرفعه العميل مع المنصة ككل (المسارات النشطة).
     */
    public function __invoke(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $rows = $this->readRows($request);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $cache = $this->loadAllPlatformCache($rows);
        $lines = $this->sortLines($this->buildLines($rows, $cache));

        return response()->json([
            'rows_read' => count($rows),
            'lines'     => $lines,
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
                    'id'            => $upload->id,
                    'file_name'     => $upload->original_name,
                    'supplier'      => $upload->supplier?->name,
                    'status'        => $upload->status,
                    'matched_count' => $upload->matched_count,
                    'created_at'    => $upload->created_at?->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    /**
     * الوضع 2: مقارنة ملف يرفعه العميل مع ملف واحد محدد من الملفات المرفوعة من الأدمن.
     */
    public function fileToUpload(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $data = $request->validate([
            'file'      => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'upload_id' => ['required', 'exists:uploads,id'],
        ]);

        $rows = $this->readRows($request);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $uploadId = (int) $data['upload_id'];
        $cache    = $this->loadUploadCache($uploadId);
        $lines    = $this->sortLines($this->buildLines($rows, $cache, $uploadId));

        return response()->json([
            'rows_read' => count($rows),
            'lines'     => $lines,
        ]);
    }

    /**
     * الوضع 3: مقارنة ملفين من الملفات المرفوعة من الأدمن مع بعضهما.
     *
     * المنتجات مربوطة بكل مورد على حدة (منتج الشيت = مورد واحد)، لذلك تتم
     * المطابقة بين الملفين عبر الاسم المُطبّع (normalized_name) وليس product_id.
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

        $indexA = $this->buildUploadsIndex((int) $data['upload_id_a']);
        $indexB = $this->buildUploadsIndex((int) $data['upload_id_b']);

        $names = array_values(
            array_unique(array_merge(array_keys($indexA), array_keys($indexB)))
        );

        $lines = [];

        foreach ($names as $name) {
            $entryA = $indexA[$name] ?? null;
            $entryB = $indexB[$name] ?? null;

            $productA = $entryA['product'] ?? null;
            $productB = $entryB['product'] ?? null;
            $offerA   = $entryA['offer'] ?? null;
            $offerB   = $entryB['offer'] ?? null;

            $productName = $productA
                ? ($productA->name_ar ?: $productA->name_en)
                : ($productB->name_ar ?: $productB->name_en);

            if ($offerA && $offerB) {
                $priceA      = $offerA->price !== null ? (float) $offerA->price : null;
                $discountA   = $offerA->discount !== null ? (float) $offerA->discount : null;
                $priceB      = $offerB->price !== null ? (float) $offerB->price : null;
                $discountB   = $offerB->discount !== null ? (float) $offerB->discount : null;

                $lines[] = [
                    'query'          => $productName,
                    'sheet'          => [
                        'name'     => $productName,
                        'price'    => $priceA,
                        'discount' => $discountA,
                    ],
                    'matched_product' => $productName,
                    'similarity'      => 100.0,
                    'platform_best'   => [
                        'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                        'area'     => $offerB->supplier?->area,
                        'phone'    => $offerB->supplier?->phone1 ?: $offerB->supplier?->phone2,
                        'price'    => $priceB,
                        'discount' => $discountB,
                    ],
                    'comparison' => [
                        'price_diff'    => ($priceA !== null && $priceB !== null)
                            ? round($priceA - $priceB, 2) : null,
                        'discount_diff' => ($discountA !== null && $discountB !== null)
                            ? round($discountA - $discountB, 2) : null,
                    ],
                    'count'    => 1,
                    'status'   => 'both',
                    'skipped'  => false,
                ];

                continue;
            }

            if ($offerA) {
                $lines[] = [
                    'query'          => $productName,
                    'sheet'          => [
                        'name'     => $productName,
                        'price'    => $offerA->price !== null ? (float) $offerA->price : null,
                        'discount' => $offerA->discount !== null ? (float) $offerA->discount : null,
                    ],
                    'matched_product' => $productName,
                    'similarity'      => 100.0,
                    'platform_best'   => null,
                    'comparison'      => ['price_diff' => null, 'discount_diff' => null],
                    'count'    => 1,
                    'status'   => 'only_a',
                    'skipped'  => false,
                ];

                continue;
            }

            // فقط موجود في الملف الثاني
            $lines[] = [
                'query'          => $productName,
                'sheet'          => null,
                'matched_product' => $productName,
                'similarity'      => 100.0,
                'platform_best'   => [
                    'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                    'area'     => $offerB->supplier?->area,
                    'phone'    => $offerB->supplier?->phone1 ?: $offerB->supplier?->phone2,
                    'price'    => $offerB->price !== null ? (float) $offerB->price : null,
                    'discount' => $offerB->discount !== null ? (float) $offerB->discount : null,
                ],
                'comparison' => ['price_diff' => null, 'discount_diff' => null],
                'count'    => 1,
                'status'   => 'only_b',
                'skipped'  => false,
            ];
        }

        $lines = $this->sortLines($lines);

        return response()->json([
            'rows_read' => count($lines),
            'lines'     => $lines,
        ]);
    }

    /**
     * بناء فهرس منتجات ملف مرفوع: normalized_name => أفضل عرض (أقل سعر) للمنتج.
     *
     * @return array<string, array{product: Product, offer: Offer}>
     */
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
                    'offer'   => $offer,
                ];
            }
        }

        return $index;
    }

    /**
     * قراءة صفوف الملف المرفوع وتنظيفه مؤقتاً بعد القراءة.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readRows(Request $request): array
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $path     = $request->file('file')->store('temp/compare-platform/' . now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            return $this->excelSearchService->readRowsAutoForPlatformCompare($fullPath, self::MAX_ROWS);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * الوضع 1: تحميل منتجات المنصة (بعروض نشطة) المطابقة لكلمات مفتاحية من الملف.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function loadAllPlatformCache(array $rows): Collection
    {
        $keywordMap = [];

        foreach ($rows as $idx => $row) {
            $query = trim((string) $row['name']);
            if (mb_strlen($query) < 3) {
                continue;
            }
            $normalized = $this->normalizer->normalize($query);
            $firstWord  = explode(' ', $normalized)[0] ?? '';
            if (mb_strlen($firstWord) < 2) {
                continue;
            }
            $keywordMap[$firstWord][] = $idx;
        }

        $productCache = [];

        $keywordChunks = array_chunk(array_keys($keywordMap), self::CHUNK_KEYWORDS);

        foreach ($keywordChunks as $keywords) {
            $query = Product::query()
                ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $q->orWhere('normalized_name', 'LIKE', $kw . '%');
                    }
                })
                ->with([
                    'offers' => function ($q) {
                        $q->active()
                            ->orderBy('price')
                            ->with('supplier:id,name,area,phone1,phone2');
                    },
                ])
                ->get();

            foreach ($query as $product) {
                if ($product->offers->isNotEmpty()) {
                    $productCache[$product->id] = $product;
                }
            }
        }

        return collect(array_values($productCache));
    }

    /**
     * الوضع 2: تحميل منتجات ملف واحد محدد من ملفات الأدمن (بكل عروضه).
     */
    private function loadUploadCache(int $uploadId): Collection
    {
        return Product::query()
            ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->whereHas('offers', function ($q) use ($uploadId) {
                $q->where('upload_id', $uploadId);
            })
            ->with([
                'offers' => function ($q) use ($uploadId) {
                    $q->where('upload_id', $uploadId)
                        ->orderBy('price')
                        ->with('supplier:id,name,area,phone1,phone2');
                },
            ])
            ->get()
            ->filter(fn (Product $p) => $p->offers->isNotEmpty());
    }

    /**
     * بناء سطور المقارنة لكل صف من الملف مقابل مجموعة المنتجات المتاحة.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildLines(array $rows, Collection $cachedProducts, ?int $uploadId = null): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $rawQuery = trim((string) $row['name']);

            if (mb_strlen($rawQuery) < 3) {
                $lines[] = [
                    'query'   => $rawQuery,
                    'sheet'   => $row,
                    'skipped' => true,
                    'reason'  => 'min_length',
                ];
                continue;
            }

            $normalizedQuery = $this->normalizer->normalize($rawQuery);
            $firstWord       = explode(' ', $normalizedQuery)[0] ?? '';

            $candidates = $firstWord !== ''
                ? $cachedProducts->filter(fn (Product $p) =>
                    str_starts_with((string) ($p->normalized_name ?? ''), $firstWord)
                )
                : $cachedProducts;

            if ($candidates->isEmpty() && mb_strlen($firstWord) >= 3) {
                $offerWith = $this->offerWithClosure($uploadId);

                $fallback = Product::query()
                    ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                    ->where(function ($q) use ($firstWord, $normalizedQuery) {
                        $q->where('normalized_name', 'LIKE', '%' . $firstWord . '%')
                          ->orWhere('normalized_name', 'LIKE', '%' . $normalizedQuery . '%');
                    })
                    ->with(['offers' => $offerWith])
                    ->limit(20)
                    ->get()
                    ->filter(fn (Product $p) => $p->offers->isNotEmpty());

                $candidates = $fallback;

                if ($candidates->isEmpty() && mb_strlen($normalizedQuery) >= 6) {
                    $partialQuery = mb_substr($normalizedQuery, 0, 8);
                    $candidates = Product::query()
                        ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                        ->where('normalized_name', 'LIKE', '%' . $partialQuery . '%')
                        ->with(['offers' => $offerWith])
                        ->limit(20)
                        ->get()
                        ->filter(fn (Product $p) => $p->offers->isNotEmpty());
                }
            }

            $bestScore   = 0.0;
            $bestProduct = null;

            foreach ($candidates as $product) {
                $score = $this->matchScore(
                    $normalizedQuery,
                    $rawQuery,
                    (string) ($product->normalized_name ?? ''),
                    (string) ($product->name_ar ?? ''),
                    (string) ($product->name_en ?? ''),
                );

                if ($score > $bestScore) {
                    $bestScore   = $score;
                    $bestProduct = $product;
                }
            }

            if ($bestScore < self::MIN_SIMILARITY || $bestProduct === null) {
                $lines[] = $this->noMatchLine($row, $rawQuery);
                continue;
            }

            $bestOffer        = $bestProduct->offers->sortBy('price')->first();
            $sheetPrice       = $row['price'];
            $sheetDiscount    = $row['discount'];
            $platformPrice    = $bestOffer ? (float) $bestOffer->price    : null;
            $platformDiscount = $bestOffer ? (float) $bestOffer->discount : null;

            $lines[] = [
                'query'               => $rawQuery,
                'sheet'               => $row,
                'search_results_count' => 1,
                'matched_product'     => $bestProduct->name_ar ?: $bestProduct->name_en,
                'similarity'          => round($bestScore, 1),
                'platform_best'       => [
                    'supplier' => $bestOffer?->supplier?->name,
                    'area'     => $bestOffer?->supplier?->area,
                    'phone'    => $bestOffer?->supplier?->phone1 ?: $bestOffer?->supplier?->phone2,
                    'price'    => $platformPrice,
                    'discount' => $platformDiscount,
                ],
                'comparison' => [
                    'price_diff'    => ($sheetPrice !== null && $platformPrice !== null)
                        ? round($sheetPrice - $platformPrice, 2) : null,
                    'discount_diff' => ($sheetDiscount !== null && $platformDiscount !== null)
                        ? round($sheetDiscount - $platformDiscount, 2) : null,
                ],
                'count'   => $bestProduct->offers->count(),
                'status'  => 'both',
                'skipped' => false,
            ];
        }

        return $lines;
    }

    /**
     * Closure لتحميل العروض حسب الوضع (كل عرض الملف المحدد، أو العروض النشطة).
     */
    private function offerWithClosure(?int $uploadId): callable
    {
        return function ($q) use ($uploadId) {
            if ($uploadId !== null) {
                $q->where('upload_id', $uploadId);
            } else {
                $q->active();
            }
            $q->orderBy('price')->with('supplier:id,name,area,phone1,phone2');
        };
    }

    /**
     * أفضل عرض (أقل سعر) لكل منتج داخل ملف مرفوع معين.
     */
    private function bestOffersByProduct(int $uploadId): Collection
    {
        return Offer::query()
            ->where('upload_id', $uploadId)
            ->with('supplier:id,name,area,phone1,phone2')
            ->orderBy('price')
            ->get()
            ->groupBy('product_id')
            ->map->first();
    }

    /**
     * ترتيب السطور: المطابقات أولاً ← بها عرض ← بالاسم.
     */
    private function sortLines(array $lines): array
    {
        usort($lines, function (array $a, array $b): int {
            $aMatch = (int) (($a['count'] ?? 0) > 0);
            $bMatch = (int) (($b['count'] ?? 0) > 0);
            if ($aMatch !== $bMatch) {
                return $bMatch <=> $aMatch;
            }
            if (($a['status'] ?? '') === 'only_b') {
                return 1;
            }
            if (($b['status'] ?? '') === 'only_b') {
                return -1;
            }
            $aOffer = (int) (!empty($a['platform_best']['supplier']));
            $bOffer = (int) (!empty($b['platform_best']['supplier']));
            if ($aOffer !== $bOffer) {
                return $bOffer <=> $aOffer;
            }
            return strcmp((string) ($a['query'] ?? ''), (string) ($b['query'] ?? ''));
        });

        return $lines;
    }

    /**
     * حساب نسبة التشابه بين اسم من الشيت واسم منتج من الـ DB.
     * + تطبيع الاختلافات الإملائية الشائعة في أسماء الأدوية
     */
    private function matchScore(
        string $normalizedQuery,
        string $rawQuery,
        string $normalizedName,
        string $nameAr,
        string $nameEn,
    ): float {
        $normQ  = $this->drugNormalize($normalizedQuery);
        $scores = [];

        if ($normalizedName !== '') {
            $normP = $this->drugNormalize($normalizedName);

            similar_text($normQ, $normP, $pct);
            $scores[] = $pct;

            similar_text($normalizedQuery, $normalizedName, $pct2);
            $scores[] = $pct2;

            $qFirst = explode(' ', $normQ)[0] ?? '';
            $pFirst = explode(' ', $normP)[0]  ?? '';
            if ($qFirst !== '' && $pFirst !== '' && str_starts_with($pFirst, $qFirst)) {
                $scores[] = min(100.0, max($pct, $pct2) + 15);
            }
        }

        if ($nameAr !== '') {
            $normAr = $this->drugNormalize($this->normalizer->normalize($nameAr));
            similar_text($normQ, $normAr, $pct);
            $scores[] = $pct;
        }

        if ($nameEn !== '') {
            similar_text(strtolower($rawQuery), strtolower($nameEn), $pct);
            $scores[] = $pct;
        }

        return $scores ? max($scores) : 0.0;
    }

    /**
     * تطبيع إملائي إضافي خاص بأسماء الأدوية العربية.
     * يوحّد الاختلافات الشائعة (اي/ي، ة/ه، كا/ك ...) لتحسين المطابقة.
     */
    private function drugNormalize(string $text): string
    {
        return str_replace(
            ['اي', 'ايي', 'ائ', 'أي', 'وى', 'ى',  'ة',  'ه',  'اى',  'كا', 'جا', 'سا'],
            ['ي',  'ي',   'ي',  'ي',  'وي', 'ي',  'ه',  'ه',  'اي',  'ك',  'ج',  'س' ],
            $text
        );
    }

    /**
     * سطر "لم يُعثر على مطابق".
     */
    private function noMatchLine(array $row, string $rawQuery): array
    {
        return [
            'query'               => $rawQuery,
            'sheet'               => $row,
            'search_results_count' => 0,
            'matched_product'     => null,
            'similarity'          => 0,
            'platform_best'       => [
                'supplier' => null,
                'area'     => null,
                'phone'    => null,
                'price'    => null,
                'discount' => null,
            ],
            'comparison' => [
                'price_diff'    => null,
                'discount_diff' => null,
            ],
            'count'   => 0,
            'status'  => 'no_match',
            'skipped' => false,
        ];
    }
}