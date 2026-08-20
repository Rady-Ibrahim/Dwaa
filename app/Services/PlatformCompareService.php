<?php

namespace App\Services;

/**
 * خدمة المطابقة المحسّنة بين ملفات Excel ومنتجات/عروض المنصة.
 *
 * الاستراتيجية (v3 — O(1) hash maps + plain arrays):
 * 1. Cache payloads = plain associative arrays + precomputed tokens/numbers.
 * 2. Hash Map Index: by_normalized O(1), by_code O(1), by_first O(1), by_first_sub O(1).
 * 3. Fuzzy only for unmatched rows via token overlap scoring.
 * 4. Offer map built internally — no redundant controller loops.
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

    /**
     * الـ entry point: يبني سطور المقارنة لصفوف الملف مقابل المنتجات المحملة.
     *
     * يعمل مع plain arrays (بـ precomputed tokens) أو Eloquent objects.
     * يبني offerMap داخلياً من cachedProducts.
     */
    public function buildLines(array $rows, array $cachedProducts, ?int $uploadId = null): array
    {
        $this->nameCache = [];
        $this->productDataCache = [];

        // بناء offerMap مرة واحدة من الكاش
        $offerMap = [];
        foreach ($cachedProducts as $product) {
            $pid = is_array($product) ? ($product['id'] ?? null) : ($product->id ?? null);
            if ($pid === null) {
                continue;
            }
            $offer = is_array($product) ? ($product['best_offer'] ?? null) : ($product->best_offer ?? $product->offers?->first());
            if ($offer) {
                $offerMap[$pid] = $offer;
            }
        }

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

            $normalizedQuery = $this->normalizeQuery($rawQuery);
            $firstWord = explode(' ', $normalizedQuery)[0] ?? '';
            $queryPrepared = $this->prepareName($normalizedQuery);

            // ── الخطوة 1a: Exact match O(1) عبر normalized_name ──
            $exactProduct = $index['by_normalized'][$normalizedQuery] ?? null;
            if ($exactProduct !== null) {
                $pid = is_array($exactProduct) ? $exactProduct['id'] : $exactProduct->id;
                $matchedProductIds[$pid] = true;
                $lines[] = [
                    'query' => $rawQuery, 'price' => $row['price'],
                    'discount' => $row['discount'],
                    'matched_product' => $this->productName($exactProduct),
                    'similarity' => 100.0, '_product_id' => $pid,
                    'platform_best' => null, 'status' => 'both',
                ];

                continue;
            }

            // ── الخطوة 1b: Exact match O(1) عبر code ──
            $queryCode = $this->extractCode($rawQuery);
            if ($queryCode !== null && isset($index['by_code'][$queryCode])) {
                $exactProduct = $index['by_code'][$queryCode];
                $pid = is_array($exactProduct) ? $exactProduct['id'] : $exactProduct->id;
                $matchedProductIds[$pid] = true;
                $lines[] = [
                    'query' => $rawQuery, 'price' => $row['price'],
                    'discount' => $row['discount'],
                    'matched_product' => $this->productName($exactProduct),
                    'similarity' => 100.0, '_product_id' => $pid,
                    'platform_best' => null, 'status' => 'both',
                ];

                continue;
            }

            // ── الخطوة 2: Fuzzy match عبر hash map tokens ──
            $candidates = $this->findCandidates($normalizedQuery, $firstWord, $index);

            // ── الخطوة 3: فلتر تقارب السعر 2% ──
            $rowPrice = (float) ($row['price'] ?? 0);
            if ($rowPrice > 0 && $candidates !== [] && $offerMap !== []) {
                $candidates = $this->filterByPriceProximity($candidates, $rowPrice, $offerMap);
            }

            // ── الخطوة 4: المطابقة النصية + Early Exit ──
            $bestScore = 0.0;
            $bestProduct = null;

            foreach ($candidates as $product) {
                $pid = is_array($product) ? $product['id'] : $product->id;
                $pd = $this->getProductData($pid, $product);
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
                    break;
                }
            }

            if ($bestScore < self::MIN_SIMILARITY || $bestProduct === null) {
                $lines[] = $this->noMatchLine($row, $rawQuery);

                continue;
            }

            $bestPid = is_array($bestProduct) ? $bestProduct['id'] : $bestProduct->id;
            $matchedProductIds[$bestPid] = true;
            $lines[] = [
                'query' => $rawQuery, 'price' => $row['price'],
                'discount' => $row['discount'],
                'matched_product' => $this->productName($bestProduct),
                'similarity' => round($bestScore, 1),
                '_product_id' => $bestPid,
                'platform_best' => null, 'status' => 'both',
            ];
        }

        $this->assignOffers($lines, $offerMap);

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

    private function buildHashMapIndex(array $products): array
    {
        $byNormalized = [];
        $byCode = [];
        $byFirst = [];
        $byFirstSub = [];

        foreach ($products as $product) {
            $normName = is_array($product) ? ($product['normalized_name'] ?? '') : ($product->normalized_name ?? '');
            $code = is_array($product) ? ($product['code'] ?? null) : ($product->code ?? null);

            if ($normName !== '') {
                $byNormalized[$normName] = $product;
            }

            if ($code !== null && $code !== '') {
                $normalizedCode = mb_strtolower(trim((string) $code));
                if (! isset($byCode[$normalizedCode])) {
                    $byCode[$normalizedCode] = $product;
                }
            }

            $tokens = is_array($product) ? ($product['tokens'] ?? []) : $this->contentTokens($normName);
            $firstWord = $tokens[0] ?? '';

            if ($firstWord !== '') {
                $byFirst[$firstWord][] = $product;
            }

            if (mb_strlen($normName) >= 8) {
                $sub = mb_substr($normName, 0, 8);
                $byFirstSub[$sub][] = $product;
            }
        }

        return [
            'by_normalized' => $byNormalized,
            'by_code' => $byCode,
            'by_first' => $byFirst,
            'by_first_sub' => $byFirstSub,
        ];
    }

    private function findCandidates(string $normalizedQuery, string $firstWord, array $index): array
    {
        $candidateMap = [];

        if ($firstWord !== '' && isset($index['by_first'][$firstWord])) {
            foreach ($index['by_first'][$firstWord] as $product) {
                $pid = is_array($product) ? $product['id'] : $product->id;
                $candidateMap[$pid] = $product;
            }
        }

        $queryTokens = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($queryTokens as $token) {
            if (mb_strlen($token) >= 3 && isset($index['by_first'][$token])) {
                foreach ($index['by_first'][$token] as $product) {
                    $pid = is_array($product) ? $product['id'] : $product->id;
                    $candidateMap[$pid] = $product;
                }
            }
        }

        if ($candidateMap === []) {
            $prefix8 = mb_strlen($normalizedQuery) >= 8 ? mb_substr($normalizedQuery, 0, 8) : null;
            if ($prefix8 !== null && isset($index['by_first_sub'][$prefix8])) {
                foreach ($index['by_first_sub'][$prefix8] as $product) {
                    $pid = is_array($product) ? $product['id'] : $product->id;
                    $candidateMap[$pid] = $product;
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

    private function matchesProductCandidate(string $normalizedQuery, string $firstWord, array $queryTokens, array|object $product): bool
    {
        $productName = is_array($product) ? ($product['normalized_name'] ?? '') : ($product->normalized_name ?? '');
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

    // ════════════════════════════════════════════════════════════════
    //  Price Proximity Filter
    // ════════════════════════════════════════════════════════════════

    private function filterByPriceProximity(array $candidates, float $rowPrice, array $offerMap): array
    {
        $result = [];
        foreach ($candidates as $product) {
            $pid = is_array($product) ? $product['id'] : $product->id;
            $offer = $offerMap[$pid] ?? null;
            $offerPrice = is_array($offer) ? ((float) ($offer['price'] ?? 0)) : ((float) ($offer->price ?? 0));
            if (! $offer || $offerPrice <= 0) {
                continue;
            }
            if (abs($offerPrice - $rowPrice) / $rowPrice <= self::PRICE_PROXIMITY_RATIO) {
                $result[] = $product;
            }
        }

        return $result;
    }

    // ════════════════════════════════════════════════════════════════
    //  Lazy Product Data + Name Scoring
    // ════════════════════════════════════════════════════════════════

    private function getProductData(int $productId, array|object $product): array
    {
        return $this->productDataCache[$productId] ??= $this->buildProductData($product);
    }

    private function buildProductData(array|object $product): array
    {
        if (is_array($product)) {
            return [
                'norm_tokens' => $product['tokens'] ?? [],
                'norm_numbers' => $product['numbers'] ?? [],
                'ar_tokens' => $product['ar_tokens'] ?? [],
                'ar_numbers' => $product['ar_numbers'] ?? [],
                'en_tokens' => $product['en_tokens'] ?? [],
                'en_numbers' => $product['en_numbers'] ?? [],
            ];
        }

        $normName = (string) ($product->normalized_name ?? '');
        $nameAr = (string) ($product->name_ar ?? '');
        $nameEn = (string) ($product->name_en ?? '');

        return [
            'norm_tokens' => $normName !== '' ? $this->prepareName($normName)['tokens'] : [],
            'norm_numbers' => $normName !== '' ? $this->prepareName($normName)['numbers'] : [],
            'ar_tokens' => $nameAr !== '' ? $this->prepareName($this->normalizeQuery($nameAr))['tokens'] : [],
            'ar_numbers' => $nameAr !== '' ? $this->prepareName($this->normalizeQuery($nameAr))['numbers'] : [],
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
    //  Text Analysis Helpers
    // ════════════════════════════════════════════════════════════════

    private function normalizeQuery(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $normalized);
        $normalized = str_replace(['ى', 'ئ', 'ة', 'ؤ', 'ء'], ['ي', 'ي', 'ه', 'و', ''], $normalized);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function extractCode(string $text): ?string
    {
        $normalized = preg_replace('/[^\p{N}]/u', '', $text);

        if ($normalized !== '' && mb_strlen($normalized) >= 3) {
            return $normalized;
        }

        return null;
    }

    private function productName(array|object $product): string
    {
        if (is_array($product)) {
            return $product['name_ar'] ?? $product['name_en'] ?? '';
        }

        return $product->name_ar ?: $product->name_en;
    }

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

    private function assignOffers(array &$lines, array $offerMap): void
    {
        foreach ($lines as &$line) {
            if (($line['status'] ?? '') !== 'both' || empty($line['_product_id'])) {
                continue;
            }

            $offer = $offerMap[$line['_product_id']] ?? null;

            $platformPrice = null;
            $discount = null;
            $supplierName = null;

            if ($offer) {
                if (is_array($offer)) {
                    $platformPrice = isset($offer['price']) ? (float) $offer['price'] : null;
                    $discount = isset($offer['discount']) ? (float) $offer['discount'] : null;
                    $supplierName = $offer['supplier_name'] ?? null;
                } else {
                    $platformPrice = (float) $offer->price;
                    $discount = (float) $offer->discount;
                    if (isset($offer->supplier) && is_object($offer->supplier)) {
                        $supplierName = $offer->supplier->name;
                    } elseif (isset($offer->supplier_name)) {
                        $supplierName = $offer->supplier_name;
                    }
                }
            }

            $line['platform_best'] = $offer ? [
                'supplier' => $supplierName,
                'price' => $platformPrice,
                'discount' => $discount,
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
