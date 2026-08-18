<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * خدمة المطابقة المحسّنة بين ملفات Excel ومنتجات/عروض المنصة.
 *
 * الاستراتيجية:
 * 1. In-Memory Hash Map Indexing: فهرسة المنتجات by first-token + by token مع
 *    ProductData lazy computation → كل صف يقارن بمرشحين قليلين (10-50) بدل58K.
 * 2. Early Exit: خروج فوري عند تطابق 100%.
 * 3. فلتر تقارب السعر 2% قبل المطابقة النصية لتضييق المرشحين.
 * 4. Log مُحسّن: ملخص فقط (start/done) بدون debug logs داخل اللوب.
 */
class PlatformCompareService
{
    private const MIN_SIMILARITY = 45.0;

    private const PRICE_PROXIMITY_RATIO = 0.02;

    private const DRUG_STOP_WORDS = [
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

    private array $nameCache = [];

    private array $productDataCache = [];

    private static ?array $stopWordMap = null;

    public function __construct(private NormalizerService $normalizer) {}

    /**
     * الـ entry point الرئيسي: يبني سطور المقارنة لصفوف الملف مقابل المنتجات المحملة.
     *
     * التدفق لكل صف:
     *   Hash Map lookup (O(1)/token) → فلتر السعر2% → drugOverlapScore → early exit
     */
    public function buildLines(array $rows, Collection $cachedProducts, ?int $uploadId = null, array $offerMap = []): array
    {
        $this->nameCache = [];
        $this->productDataCache = [];

        $index = $this->buildHashMapIndex($cachedProducts);
        $lines = [];
        $matchedProductIds = [];

        foreach ($rows as $row) {
            $rawQuery = trim((string) $row['name']);

            if (mb_strlen($rawQuery) < 3) {
                $lines[] = [
                    'query' => $rawQuery, 'price' => $row['price'] ?? null,
                    'discount' => $row['discount'] ?? null, 'matched_product' => null,
                    'similarity' => 0, 'platform_best' => null, 'status' => 'skipped',
                ];

                continue;
            }

            $normalizedQuery = $this->normalizer->normalize($rawQuery);
            $firstWord = explode(' ', $normalizedQuery)[0] ?? '';
            $queryPrepared = $this->prepareName($normalizedQuery);

            // ── الخطوة 1: جلب المرشحين من الـ Hash Map ──
            $candidates = $this->findCandidates($normalizedQuery, $firstWord, $index);

            if ($candidates === [] && mb_strlen($firstWord) >= 3) {
                $candidates = $this->fallbackCandidates($firstWord, $normalizedQuery, $cachedProducts);
            }

            // ── الخطوة 2: فلتر تقارب السعر2% ──
            $rowPrice = (float) ($row['price'] ?? 0);
            if ($rowPrice > 0 && $candidates !== [] && $offerMap !== []) {
                $candidates = $this->filterByPriceProximity($candidates, $rowPrice, $offerMap);
            }

            // ── الخطوة 3: المطابقة النصية + Early Exit ──
            $bestScore = 0.0;
            $bestProduct = null;

            foreach ($candidates as $product) {
                $pd = $this->getProductData($product->id, $product);
                $nameScore = $this->computeNameScore($queryPrepared, $pd);

                if ($nameScore <= 0.0) {
                    continue;
                }

                $score = $this->applyPriceScore(
                    $rowPrice > 0 ? $rowPrice : null,
                    null,
                    $nameScore,
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestProduct = $product;
                }

                if ($bestScore >= 100.0) {
                    break; // Early Exit: تطابق تام، لا حاجة لتقييم باقي المرشحين
                }
            }

            if ($bestScore < self::MIN_SIMILARITY || $bestProduct === null) {
                $lines[] = $this->noMatchLine($row, $rawQuery);

                continue;
            }

            $matchedProductIds[$bestProduct->id] = true;

            $lines[] = [
                'query' => $rawQuery,
                'price' => $row['price'],
                'discount' => $row['discount'],
                'matched_product' => $bestProduct->name_ar ?: $bestProduct->name_en,
                'similarity' => round($bestScore, 1),
                '_product_id' => $bestProduct->id,
                'platform_best' => null,
                'status' => 'both',
            ];
        }

        $this->assignOffers($lines, $cachedProducts, $offerMap, $uploadId);

        return $lines;
    }

    public function sortLines(array $lines): array
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
    //  Hash Map Indexing
    // ════════════════════════════════════════════════════════════════

    /**
     * بناء فهرس hash map: by_first (أول كلمة) + by_token (كل كلمة ≥3 أحرف).
     * كل قيمة = array<product_id => Product> → lookup O(1).
     *
     * @return array{by_first: array<string, array<int, Product>>, by_token: array<string, array<int, Product>>}
     */
    private function buildHashMapIndex(Collection $products): array
    {
        $byFirst = [];
        $byToken = [];

        foreach ($products as $product) {
            $name = (string) ($product->normalized_name ?? '');
            $tokens = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $first = $tokens[0] ?? '';
            if ($first !== '') {
                $byFirst[$first][$product->id] = $product;
            }

            foreach ($tokens as $token) {
                if (mb_strlen($token) >= 3) {
                    $byToken[$token][$product->id] = $product;
                }
            }
        }

        return ['by_first' => $byFirst, 'by_token' => $byToken];
    }

    /**
     * جلب المرشحين من الـ hash map: lookup O(1) لكل token، ثم فلتر by matchesPlatformProductCandidate.
     *
     * @return list<Product>
     */
    private function findCandidates(string $normalizedQuery, string $firstWord, array $index): array
    {
        $candidateMap = [];

        if ($firstWord !== '' && isset($index['by_first'][$firstWord])) {
            foreach ($index['by_first'][$firstWord] as $id => $product) {
                $candidateMap[$id] = $product;
            }
        }

        $queryTokens = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($queryTokens as $token) {
            if (mb_strlen($token) >= 3 && isset($index['by_token'][$token])) {
                foreach ($index['by_token'][$token] as $id => $product) {
                    $candidateMap[$id] = $product;
                }
            }
        }

        if ($candidateMap === []) {
            return [];
        }

        $result = [];
        foreach ($candidateMap as $product) {
            if ($this->matchesProductCandidate($normalizedQuery, $firstWord, $queryTokens, $product)) {
                $result[] = $product;
            }
        }

        return $result;
    }

    private function matchesProductCandidate(string $normalizedQuery, string $firstWord, array $queryTokens, Product $product): bool
    {
        $productName = (string) ($product->normalized_name ?? '');
        if ($productName === '') {
            return false;
        }

        if ($firstWord !== '' && (str_starts_with($productName, $firstWord) || str_contains($productName, $firstWord))) {
            return true;
        }

        foreach ($queryTokens as $token) {
            if (mb_strlen($token) >= 3 && str_contains($productName, $token)) {
                return true;
            }
        }

        $prefixLen = min(mb_strlen($normalizedQuery), mb_strlen($productName), 6);
        if ($prefixLen >= 4) {
            return mb_substr($normalizedQuery, 0, $prefixLen) === mb_substr($productName, 0, $prefixLen);
        }

        return false;
    }

    private function fallbackCandidates(string $firstWord, string $normalizedQuery, Collection $cachedProducts): array
    {
        $partialQuery = mb_strlen($normalizedQuery) >= 6 ? mb_substr($normalizedQuery, 0, 8) : null;
        $result = [];

        foreach ($cachedProducts as $product) {
            $name = (string) ($product->normalized_name ?? '');
            if ($name === '') {
                continue;
            }
            if (str_contains($name, $firstWord) || str_contains($name, $normalizedQuery)) {
                $result[] = $product;
            } elseif ($partialQuery !== null && str_contains($name, $partialQuery)) {
                $result[] = $product;
            }
        }

        return $result;
    }

    // ════════════════════════════════════════════════════════════════
    //  Price Proximity Filter
    // ════════════════════════════════════════════════════════════════

    private function filterByPriceProximity(array $candidates, float $rowPrice, array $offerMap): array
    {
        $result = [];
        foreach ($candidates as $product) {
            $offer = $offerMap[$product->id] ?? null;
            if (! $offer || (float) $offer->price <= 0) {
                continue;
            }
            $offerPrice = (float) $offer->price;
            if (abs($offerPrice - $rowPrice) / $rowPrice <= self::PRICE_PROXIMITY_RATIO) {
                $result[] = $product;
            }
        }

        return $result;
    }

    // ════════════════════════════════════════════════════════════════
    //  Lazy Product Data + Name Scoring
    // ════════════════════════════════════════════════════════════════

    /**
     * تجهيز بيانات المنتج مرة واحدة فقط (lazy) ثم إعادة استخدامها في كل مقارنة.
     */
    private function getProductData(int $productId, Product $product): array
    {
        return $this->productDataCache[$productId] ??= $this->buildProductData($product);
    }

    private function buildProductData(Product $product): array
    {
        $normName = (string) ($product->normalized_name ?? '');
        $nameAr = (string) ($product->name_ar ?? '');
        $nameEn = (string) ($product->name_en ?? '');

        return [
            'product' => $product,
            'norm_tokens' => $normName !== '' ? $this->prepareName($normName)['tokens'] : [],
            'norm_numbers' => $normName !== '' ? $this->prepareName($normName)['numbers'] : [],
            'ar_tokens' => $nameAr !== '' ? $this->prepareName($this->normalizer->normalize($nameAr))['tokens'] : [],
            'ar_numbers' => $nameAr !== '' ? $this->prepareName($this->normalizer->normalize($nameAr))['numbers'] : [],
            'en_tokens' => $nameEn !== '' ? $this->prepareName($nameEn)['tokens'] : [],
            'en_numbers' => $nameEn !== '' ? $this->prepareName($nameEn)['numbers'] : [],
        ];
    }

    private function computeNameScore(array $queryPrepared, array $pd): float
    {
        $nameScore = 0.0;

        if ($pd['norm_tokens'] !== []) {
            $nameScore = max($nameScore, $this->drugOverlapScorePrepared(
                $queryPrepared['tokens'], $pd['norm_tokens'],
                $queryPrepared['numbers'], $pd['norm_numbers'],
            ));
        }
        if ($nameScore < 100.0 && $pd['ar_tokens'] !== []) {
            $nameScore = max($nameScore, $this->drugOverlapScorePrepared(
                $queryPrepared['tokens'], $pd['ar_tokens'],
                $queryPrepared['numbers'], $pd['ar_numbers'],
            ));
        }
        if ($nameScore < 100.0 && $pd['en_tokens'] !== []) {
            $nameScore = max($nameScore, $this->drugOverlapScorePrepared(
                $queryPrepared['tokens'], $pd['en_tokens'],
                $queryPrepared['numbers'], $pd['en_numbers'],
            ));
        }

        return $nameScore;
    }

    // ════════════════════════════════════════════════════════════════
    //  Text Analysis Helpers (content tokens, numbers, overlap score)
    // ════════════════════════════════════════════════════════════════

    private function prepareName(string $name): array
    {
        return $this->nameCache[$name] ??= [
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
                $lower,
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
        return self::$stopWordMap ??= array_flip(self::DRUG_STOP_WORDS);
    }

    private function contentNumbers(string $name): array
    {
        $text = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $name,
        );

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

        if ($shared === []) {
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

        if ($ratio >= 0.97) {
            return min(100.0, $nameScore + 10);
        }
        if ($ratio < 0.80) {
            return $nameScore * 0.3;
        }
        if ($nameScore < 100.0) {
            return $nameScore * 0.6;
        }

        return $nameScore;
    }

    // ════════════════════════════════════════════════════════════════
    //  Offer Assignment + Output Helpers
    // ════════════════════════════════════════════════════════════════

    private function assignOffers(array &$lines, Collection $cachedProducts, array &$offerMap, ?int $uploadId): void
    {
        if ($uploadId !== null) {
            $offerMap = [];
            foreach ($cachedProducts as $product) {
                $offer = $product->offers->first();
                if ($offer) {
                    $offerMap[$product->id] = $offer;
                }
            }
        }

        foreach ($lines as &$line) {
            if (($line['status'] ?? '') !== 'both' || empty($line['_product_id'])) {
                continue;
            }

            $offer = $offerMap[$line['_product_id']] ?? null;
            $platformPrice = $offer ? (float) $offer->price : null;

            $line['platform_best'] = $offer ? [
                'supplier' => $offer->supplier?->name,
                'price' => $platformPrice,
                'discount' => (float) $offer->discount,
            ] : null;
            unset($line['_product_id']);
        }
        unset($line);
    }

    private function noMatchLine(array $row, string $rawQuery): array
    {
        return [
            'query' => $rawQuery,
            'price' => $row['price'] ?? null,
            'discount' => $row['discount'] ?? null,
            'matched_product' => null,
            'similarity' => 0,
            'platform_best' => null,
            'status' => 'no_match',
        ];
    }
}
