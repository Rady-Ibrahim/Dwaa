<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(private NormalizerService $normalizer) {}

    /**
     * @param  array{
     *     log?: bool,
     *     source?: string,
     *     bulk_session_id?: ?string,
     *     meta?: ?array<string, mixed>
     * }  $logOptions
     * @param  array{
     *     min_price?: ?float,
     *     max_price?: ?float,
     *     date_filter?: ?int
     * }  $filters
     */
    public function search(User $user, string $query, int $limit = 20, array $logOptions = [], array $filters = []): array
    {
        $products = $this->fetchProducts($query, $limit);
        $hadOffers = $products->contains(fn(Product $p) => $p->offers->isNotEmpty());

        $log = array_merge([
            'log' => true,
            'source' => SearchLog::SOURCE_TEXT,
            'bulk_session_id' => null,
            'meta' => null,
        ], $logOptions);

        if ($log['log']) {
            SearchLog::query()->create([
                'user_id' => $user->id,
                'source' => $log['source'],
                'bulk_session_id' => $log['bulk_session_id'],
                'query' => $query,
                'product_id' => $products->first()?->id,
                'results_count' => $products->count(),
                'had_offers' => $hadOffers,
                'meta' => $log['meta'],
            ]);
        }

        $results = $products->map(fn(Product $product) => $this->formatProduct($product, $filters))
            ->filter(fn($product) => !empty($product['offers']))
            ->sortByDesc(fn($product) => $product['summary']['highest_discount'] ?? 0);

        return [
            'query' => $query,
            'count' => $results->count(),
            'results' => $results->values()->all(),
        ];
    }

    /**
     * Multi-stage pharmaceutical search: 1) Primary word 2) Secondary words 3) Dosage
     * Better for medications like "اتور 10مجم" vs "اتور 20مجم"
     */
    public function pharmacySearch(string $query, int $limit = 20): Collection
    {
        $trim = trim($query);
        if ($trim === '') {
            return collect();
        }

        // Extract query components
        $components = $this->extractPharmacyComponents($trim);

        if (empty($components['words'])) {
            return $this->fetchProducts($query, $limit);
        }

        // Stage 1: Find candidates using primary word (first word)
        $primary_word = $components['words'][0];
        $stage1 = $this->getProductsByPrimaryWord($primary_word, $limit * 3);

        if ($stage1->isEmpty()) {
            return $this->fetchProducts($query, $limit);
        }

        // Stage 2: Re-rank by secondary words and dosage matching
        $scored = $stage1->map(function (Product $product) use ($components, $query) {
            $score = $this->calculatePharmacyMatchScore($product, $components, $query);
            return ['product' => $product, 'score' => $score];
        })
            ->filter(fn($entry) => $entry['score'] >= 65)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('product');

        return $scored;
    }

    /**
     * Extract words, dosages, and other components from pharmaceutical query
     */
    private function extractPharmacyComponents(string $text): array
    {
        $normalized = $this->normalizer->normalize($text);
        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Extract dosage patterns: "10مجم", "20جرام", etc.
        $dosages = [];
        preg_match_all('/(\d+(?:\.\d+)?)\s*(مجم|ملج|جرام|جم|ج|ml|iu)/ui', $text, $matches);
        if (!empty($matches[0])) {
            $dosages = array_map(fn($m) => mb_strtolower($m, 'UTF-8'), $matches[0]);
        }

        return [
            'words' => $words,
            'dosages' => $dosages,
            'raw_text' => $text,
        ];
    }

    /**
     * Get products matching primary word with offers loaded
     */
    private function getProductsByPrimaryWord(string $word, int $limit): Collection
    {
        if (mb_strlen($word) < 2) {
            return collect();
        }

        $with = [
            'supplier:id,name,area,phone1,phone2',
            'offers' => function ($q) {
                $q->active()
                    ->orderBy('price')
                    ->with(['supplier:id,name,area,phone1,phone2', 'upload:id,finished_at']);
            },
        ];

        return Product::query()
            ->where(function ($q) use ($word) {
                $q->where('normalized_name', 'LIKE', $word . '%')
                    ->orWhere('name_ar', 'LIKE', '%' . $word . '%')
                    ->orWhere('name_en', 'LIKE', '%' . $word . '%');
            })
            ->with($with)
            ->select(['id', 'supplier_id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate match score based on word matching and dosage matching
     */
    private function calculatePharmacyMatchScore(Product $product, array $components, string $queryText): float
    {
        $product_text = mb_strtolower($product->name_ar . ' ' . $product->name_en, 'UTF-8');
        $product_normalized = mb_strtolower((string)($product->normalized_name ?? ''), 'UTF-8');

        $queryWords = $components['words'];
        if ($queryWords === []) {
            return 0.0;
        }

        $totalWeight = array_sum(array_map([$this, 'queryTokenWeight'], $queryWords));
        $matchedWeight = 0.0;
        foreach ($queryWords as $word) {
            if (str_contains($product_normalized, $word) || str_contains($product_text, $word)) {
                $matchedWeight += $this->queryTokenWeight($word);
            }
        }

        $wordMatchScore = $totalWeight > 0 ? ($matchedWeight / $totalWeight) * 100 : 0;
        $score = $wordMatchScore;

        if ($this->queryExactPhraseMatches($product_normalized, $queryWords)) {
            $score += 40;
        }

        if ($this->queryStartsWithSequence($product_normalized, $queryWords)) {
            $score += 20;
        }

        $score += $this->matchDosageToProduct($product_text, $components['dosages']) * 0.4;

        return $score;
    }

    private function queryTokenWeight(string $token): float
    {
        if (preg_match('/^[\d\.\/\-]+$/u', $token)) {
            return 0.05;
        }

        if (preg_match('/\d/u', $token) && !preg_match('/\p{L}/u', $token)) {
            return 0.10;
        }

        return 1.0;
    }

    private function queryExactPhraseMatches(string $normalizedProduct, array $queryWords): bool
    {
        if ($queryWords === []) {
            return false;
        }

        return str_contains($normalizedProduct, implode(' ', $queryWords));
    }

    private function queryStartsWithSequence(string $normalizedProduct, array $queryWords): bool
    {
        if (count($queryWords) < 2) {
            return false;
        }

        return str_contains($normalizedProduct, $queryWords[0] . ' ' . $queryWords[1]);
    }

    /**
     * Match dosages in product name with query dosages
     */
    private function matchDosageToProduct(string $productText, array $queryDosages): float
    {
        if (empty($queryDosages)) {
            return 0; // No explicit dosage in query
        }

        // Extract product dosages
        preg_match_all('/(\d+(?:\.\d+)?)\s*(مجم|ملج|جرام|جم|ج|ml|iu)/ui', $productText, $matches);
        $product_dosages = array_map(fn($m) => mb_strtolower($m, 'UTF-8'), $matches[0] ?? []);

        if (empty($product_dosages)) {
            return 0; // Product has no dosage or query dosage is not explicit
        }

        // Check if any product dosage matches query dosage
        foreach ($queryDosages as $q_dosage) {
            foreach ($product_dosages as $p_dosage) {
                if (strcasecmp(trim($q_dosage), trim($p_dosage)) === 0) {
                    return 100; // Perfect match!
                }
                // Partial match (same number, different unit)
                if (preg_match('/^(\d+)/', $q_dosage, $m1) && preg_match('/^(\d+)/', $p_dosage, $m2)) {
                    if ($m1[1] === $m2[1]) {
                        return 80;
                    }
                }
            }
        }

        return 30; // Different dosages
    }

    /**
     * @return Collection<int, Product>
     */
    public function fetchProducts(string $query, int $limit = 20): Collection
    {
        $trim = trim($query);
        if ($trim === '') {
            return collect();
        }

        $queryFold = $this->normalizer->phoneticConsonantKey($trim);

        $with = [
            'supplier:id,name,area,phone1,phone2',
            'offers' => function ($q) {
                $q->active()
                    ->orderBy('price')
                    ->with(['supplier:id,name,area,phone1,phone2', 'upload:id,finished_at']);
            },
        ];

        // 1) Prefix (keyword starts with query terms) => يعطي أولوية للبحث العادي عند أول 3 أحرف
        $prefix = Product::query()
            ->tap(fn($q) => $this->normalizer->applyPrefixProductSearch($q, $trim))
            ->with($with)
            ->select(['id', 'supplier_id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($prefix->count() >= $limit) {
            return $prefix;
        }

        // 2) Flexible contains (رمز باقي النتائج بعد الـ prefix)
        $primary = $prefix;
        $needed = $limit - $primary->count();
        if ($needed > 0) {
            $seen = array_flip($primary->modelKeys());
            $extraSubstring = Product::query()
                ->tap(fn($q) => $this->normalizer->applyFlexibleProductSearch($q, $trim))
                ->whereNotIn('id', array_keys($seen))
                ->with($with)
                ->select(['id', 'supplier_id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                ->orderBy('id')
                ->limit($needed)
                ->get();

            $primary = $primary->concat($extraSubstring)->values();
        }

        if ($primary->count() >= $limit || strlen($queryFold) < 3) {
            return $primary;
        }

        $needed = $limit - $primary->count();
        $seen = array_flip($primary->modelKeys());

        $extraIds = [];
        $queryFirstWord = $this->firstWordToken($trim);
        foreach (Product::query()->select(['id', 'normalized_name', 'name_ar', 'name_en'])->orderBy('id')->cursor() as $row) {
            if (array_key_exists($row->getKey(), $seen)) {
                continue;
            }
            $matchedByPhonetic = $this->normalizer->productTextMatchesPhoneticFold(
                (string) ($row->normalized_name ?? ''),
                $row->name_ar,
                $row->name_en,
                $queryFold
            );
            $matchedByFirstWord = $this->matchesQueryByFirstWordSimilarity(
                $queryFirstWord,
                (string) ($row->normalized_name ?? ''),
                $row->name_ar,
                $row->name_en
            );

            if ($matchedByPhonetic || $matchedByFirstWord) {
                $extraIds[] = $row->id;
                if (count($extraIds) >= $needed) {
                    break;
                }
            }
        }

        if ($extraIds === []) {
            return $primary;
        }

        $extra = Product::query()
            ->whereIn('id', $extraIds)
            ->with($with)
            ->select(['id', 'supplier_id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->get();

        return $primary->concat($extra)->values();
    }

    public function formatProduct(Product $product, array $filters = []): array
    {
        /** @var Collection<int, Offer> $offers */
        $offers = $product->offers;
        if ($product->supplier_id) {
            $offers = $offers->where('supplier_id', $product->supplier_id)->values();
        }

        // Apply single price filter
        if (isset($filters['price'])) {
            $targetPrice = (float) $filters['price'];
            $offers = $offers->filter(function ($offer) use ($targetPrice) {
                return (float) $offer->price === $targetPrice;
            });
        }

        // Apply date filter
        if (isset($filters['date_filter']) && $filters['date_filter']) {
            $hours = (int) $filters['date_filter'];
            $cutoffDate = now()->subHours($hours);
            $offers = $offers->filter(function ($offer) use ($cutoffDate) {
                return $offer->upload && $offer->upload->finished_at && $offer->upload->finished_at >= $cutoffDate;
            });
        }

        $lowestPrice = $offers->min('price');
        $highestDiscount = $offers->max('discount');

        return [
            'id' => $product->id,
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'code' => $product->code,
            'source_supplier' => $product->supplier_id ? [
                'id' => $product->supplier_id,
                'name' => $product->supplier?->name,
                'area' => $product->supplier?->area,
            ] : null,
            'summary' => [
                'suppliers_count' => $offers->count(),
                'lowest_price' => $lowestPrice,
                'highest_discount' => $highestDiscount,
            ],
            'offers' => $offers->sortByDesc('discount')->map(function ($offer) use ($lowestPrice, $highestDiscount) {
                return [
                    'supplier' => $offer->supplier->name,
                    'area' => $offer->supplier->area,
                    'supplier_phone' => $offer->supplier->phone1 ?: $offer->supplier->phone2,
                    'price' => (float) $offer->price,
                    'discount' => (float) $offer->discount,
                    'bonus' => $offer->bonus,
                    'expires_at' => $offer->expires_at->toDateString(),
                    'upload_date' => $offer->upload?->finished_at?->format('Y-m-d'),
                    'is_lowest_price' => (float) $offer->price === (float) $lowestPrice,
                    'is_best_discount' => (float) $offer->discount === (float) $highestDiscount,
                ];
            })->values()->all(),
        ];
    }

    private function matchesQueryByFirstWordSimilarity(string $query, ?string ...$haystacks): bool
    {
        $queryWord = $this->firstWordToken($query);
        if (mb_strlen($queryWord) < 3) {
            return false;
        }

        foreach ($haystacks as $haystack) {
            $candidateWord = $this->firstWordToken((string) ($haystack ?? ''));
            if (mb_strlen($candidateWord) < 3) {
                continue;
            }

            if ($this->isOneEditOrLess($queryWord, $candidateWord)) {
                return true;
            }
        }

        return false;
    }

    private function firstWordToken(string $value): string
    {
        $normalized = $this->normalizer->normalize($value);
        if ($normalized === '') {
            return '';
        }

        return explode(' ', $normalized)[0] ?? '';
    }

    private function isOneEditOrLess(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $lenA = mb_strlen($a);
        $lenB = mb_strlen($b);
        if (abs($lenA - $lenB) > 1) {
            return false;
        }

        $charsA = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charsB = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $i = 0;
        $j = 0;
        $edits = 0;

        while ($i < count($charsA) && $j < count($charsB)) {
            if ($charsA[$i] === $charsB[$j]) {
                $i++;
                $j++;

                continue;
            }

            $edits++;
            if ($edits > 1) {
                return false;
            }

            if ($lenA > $lenB) {
                $i++;
            } elseif ($lenB > $lenA) {
                $j++;
            } else {
                $i++;
                $j++;
            }
        }

        if ($i < count($charsA) || $j < count($charsB)) {
            $edits++;
        }

        return $edits <= 1;
    }
}
