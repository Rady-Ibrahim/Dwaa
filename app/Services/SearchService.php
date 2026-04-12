<?php

namespace App\Services;

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
        $normalized = $this->normalizer->normalize($query);

        return Product::query()
            ->where(function ($q) use ($normalized) {
                $q->where('normalized_name', 'LIKE', '%'.$normalized.'%')
                    ->orWhereHas('aliases', function ($alias) use ($normalized) {
                        $alias->where('normalized_name', 'LIKE', '%'.$normalized.'%');
                    });
            })
            ->with([
                'supplier:id,name,area',
                'offers' => function ($q) {
                    $q->active()
                        ->orderBy('price')
                        ->with(['supplier:id,name,area']);
                },
            ])
            ->select(['id', 'supplier_id', 'name_ar', 'name_en', 'code'])
            ->limit($limit)
            ->get();
    }

    private function formatProduct(Product $product): array
    {
        /** @var Collection<int, \App\Models\Offer> $offers */
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
}
