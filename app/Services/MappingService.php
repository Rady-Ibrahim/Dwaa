<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\UnmatchedProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MappingService
{
    public function __construct(private NormalizerService $normalizer) {}

    public function linkToExisting(UnmatchedProduct $item, int $productId): UnmatchedProduct
    {
        $this->assertPending($item);

        DB::transaction(function () use ($item, $productId) {
            ProductAlias::query()->firstOrCreate(
                [
                    'product_id' => $productId,
                    'normalized_name' => $item->normalized_name,
                ],
                [
                    'name' => $item->raw_name,
                ]
            );

            $item->update([
                'status' => 'resolved',
                'resolved_product_id' => $productId,
                'resolved_at' => now(),
            ]);
        });

        return $item->fresh('resolvedProduct');
    }

    public function createFromUnmatched(UnmatchedProduct $item, array $data): Product
    {
        $this->assertPending($item);

        return DB::transaction(function () use ($item, $data) {
            $product = Product::query()->create([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'] ?? null,
                'code' => $data['code'],
                'normalized_name' => $item->normalized_name,
            ]);

            ProductAlias::query()->create([
                'product_id' => $product->id,
                'name' => $item->raw_name,
                'normalized_name' => $item->normalized_name,
            ]);

            $item->update([
                'status' => 'resolved',
                'resolved_product_id' => $product->id,
                'resolved_at' => now(),
            ]);

            return $product;
        });
    }

    public function ignore(UnmatchedProduct $item): void
    {
        $item->update(['status' => 'ignored']);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkLink(array $ids, int $productId): int
    {
        $items = UnmatchedProduct::query()
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->get();

        DB::transaction(function () use ($items, $productId) {
            foreach ($items as $item) {
                ProductAlias::query()->firstOrCreate(
                    [
                        'product_id' => $productId,
                        'normalized_name' => $item->normalized_name,
                    ],
                    [
                        'name' => $item->raw_name,
                    ]
                );

                $item->update([
                    'status' => 'resolved',
                    'resolved_product_id' => $productId,
                    'resolved_at' => now(),
                ]);
            }
        });

        return $items->count();
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkIgnore(array $ids): int
    {
        return UnmatchedProduct::query()
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->update(['status' => 'ignored']);
    }

    public function searchProducts(string $query, int $limit = 8): \Illuminate\Support\Collection
    {
        $normalized = $this->normalizer->normalize($query);

        return Product::query()
            ->where(function ($q) use ($normalized, $query) {
                $q->where('normalized_name', 'LIKE', '%'.$normalized.'%')
                    ->orWhere('name_en', 'LIKE', '%'.$query.'%')
                    ->orWhere('code', 'LIKE', '%'.$query.'%');
            })
            ->select(['id', 'name_ar', 'name_en', 'code'])
            ->limit($limit)
            ->get();
    }

    private function assertPending(UnmatchedProduct $item): void
    {
        if ($item->status !== 'pending') {
            throw ValidationException::withMessages([
                'item' => ['العنصر ليس في حالة معلق'],
            ]);
        }
    }
}
