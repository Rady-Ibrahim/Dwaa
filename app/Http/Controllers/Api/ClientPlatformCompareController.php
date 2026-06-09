<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ExcelSearchService;
use App\Services\NormalizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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

    public function __invoke(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $path     = $request->file('file')->store('temp/compare-platform/' . now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            // ── 1. اقرأ صفوف الشيت ───────────────────────────────────────────
            $rows = $this->excelSearchService->readRowsAutoForPlatformCompare($fullPath, self::MAX_ROWS);

            if (empty($rows)) {
                return response()->json([
                    'rows_read' => 0,
                    'lines'     => [],
                    'error'     => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.',
                ], 422);
            }

            // ── 2. استخرج الكلمة الأولى من كل اسم (للبحث الجماعي) ────────────
            //    بدل 757 query → نجمع الـ keywords ونعمل queries أقل بكتير
            $keywordMap = []; // normalized_first_word => [row_indices]
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

            // ── 3. جلب المنتجات المرتبطة بكل keyword دفعة دفعة ──────────────
            //    نجيب المنتجات مرة واحدة per chunk من الـ keywords
            $productCache = []; // product_id => Product (مع offers)

            $keywordChunks = array_chunk(array_keys($keywordMap), self::CHUNK_KEYWORDS);

            foreach ($keywordChunks as $keywords) {
                // بناء WHERE OR query: normalized_name LIKE 'word%' OR ...
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

            $cachedProducts = collect(array_values($productCache));

            // ── 4. قارن كل صف مع المنتجات المجلوبة ──────────────────────────
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

                // فلتر المنتجات التي تبدأ بنفس الكلمة الأولى (تضييق نطاق المقارنة)
                $candidates = $firstWord !== ''
                    ? $cachedProducts->filter(fn(Product $p) =>
                        str_starts_with((string) ($p->normalized_name ?? ''), $firstWord)
                    )
                    : $cachedProducts;

                // لو مفيش candidates من الـ cache، اعمل fallback query مباشرة
                if ($candidates->isEmpty() && mb_strlen($firstWord) >= 3) {
                    // نجرب بـ LIKE contains بدل prefix
                    $fallback = Product::query()
                        ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                        ->where(function ($q) use ($firstWord, $normalizedQuery) {
                            $q->where('normalized_name', 'LIKE', '%' . $firstWord . '%')
                              ->orWhere('normalized_name', 'LIKE', '%' . $normalizedQuery . '%');
                        })
                        ->with([
                            'offers' => function ($q) {
                                $q->active()->orderBy('price')->with('supplier:id,name,area,phone1,phone2');
                            },
                        ])
                        ->limit(20)
                        ->get()
                        ->filter(fn(Product $p) => $p->offers->isNotEmpty());

                    $candidates = $fallback;

                    // لو لسه فاضي، جرب الكلمتين الأولى مع contains
                    if ($candidates->isEmpty() && mb_strlen($normalizedQuery) >= 6) {
                        $partialQuery = mb_substr($normalizedQuery, 0, 8);
                        $candidates = Product::query()
                            ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                            ->where('normalized_name', 'LIKE', '%' . $partialQuery . '%')
                            ->with([
                                'offers' => function ($q) {
                                    $q->active()->orderBy('price')->with('supplier:id,name,area,phone1,phone2');
                                },
                            ])
                            ->limit(20)
                            ->get()
                            ->filter(fn(Product $p) => $p->offers->isNotEmpty());
                    }
                }

                // إيجاد أفضل مطابقة
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
                    'skipped' => false,
                ];
            }

            // ── 5. ترتيب: المطابقات أولاً ← بها عرض ← بالاسم ───────────────
            usort($lines, function (array $a, array $b): int {
                $aMatch = (int) (($a['count'] ?? 0) > 0);
                $bMatch = (int) (($b['count'] ?? 0) > 0);
                if ($aMatch !== $bMatch) {
                    return $bMatch <=> $aMatch;
                }
                $aOffer = (int) (!empty($a['platform_best']['supplier']));
                $bOffer = (int) (!empty($b['platform_best']['supplier']));
                if ($aOffer !== $bOffer) {
                    return $bOffer <=> $aOffer;
                }
                return strcmp((string) ($a['query'] ?? ''), (string) ($b['query'] ?? ''));
            });

            return response()->json([
                'rows_read' => count($rows),
                'lines'     => $lines,
            ]);

        } finally {
            Storage::disk('local')->delete($path);
        }
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

            // مقارنة بعد التطبيع الإملائي
            similar_text($normQ, $normP, $pct);
            $scores[] = $pct;

            // مقارنة مباشرة بدون drug normalize
            similar_text($normalizedQuery, $normalizedName, $pct2);
            $scores[] = $pct2;

            // مكافأة لو الكلمة الأولى في المنتج تبدأ بنفس بداية الاستعلام
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
            'skipped' => false,
        ];
    }
}
