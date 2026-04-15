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
     */
    public function search(User $user, string $query, int $limit = 20, array $logOptions = []): array
    {
        $products = $this->fetchProducts($query, $limit);
        $hadOffers = $products->contains(fn (Product $p) => $p->offers->isNotEmpty());

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

        $results = $products->map(fn (Product $product) => $this->formatProduct($product));

        return [
            'query' => $query,
            'count' => $results->count(),
            'results' => $results->values()->all(),
        ];
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
            'supplier:id,name,area',
            'offers' => function ($q) {
                $q->active()
                    ->orderBy('price')
                    ->with(['supplier:id,name,area']);
            },
        ];

        $primary = Product::query()
            ->tap(fn ($q) => $this->normalizer->applyFlexibleProductSearch($q, $trim))
            ->with($with)
            ->select(['id', 'supplier_id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->limit($limit)
            ->get();

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

    private function formatProduct(Product $product): array
    {
        /** @var Collection<int, Offer> $offers */
        $offers = $product->offers;
        if ($product->supplier_id) {
            $offers = $offers->where('supplier_id', $product->supplier_id)->values();
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
            'offers' => $offers->map(function ($offer) use ($lowestPrice, $highestDiscount) {
                return [
                    'supplier' => $offer->supplier->name,
                    'area' => $offer->supplier->area,
                    'price' => (float) $offer->price,
                    'discount' => (float) $offer->discount,
                    'bonus' => $offer->bonus,
                    'expires_at' => $offer->expires_at->toDateString(),
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
