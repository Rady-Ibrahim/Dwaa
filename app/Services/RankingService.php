<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierRanking;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public const SORT_ITEMS = 'items';

    public const SORT_DISCOUNT = 'discount';

    /**
     * Rebuild ranking rows for all active suppliers.
     */
    public function recalculateAll(): int
    {
        $agg = $this->aggregateQuery()->get()->keyBy('supplier_id');
        $indexedAt = now();
        $count = 0;

        foreach (Supplier::query()->where('is_active', true)->pluck('id') as $supplierId) {
            $row = $agg->get($supplierId);

            SupplierRanking::query()->updateOrCreate(
                ['supplier_id' => $supplierId],
                [
                    'total_items_count' => (int) ($row->total_items ?? 0),
                    'discount_quality_index' => $this->dqiValue($row),
                    'indexed_at' => $indexedAt,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Refresh a single supplier's ranking after a successful upload.
     */
    public function recalculateForSupplier(int $supplierId): void
    {
        $row = $this->aggregateQuery()->where('supplier_id', $supplierId)->first();

        SupplierRanking::query()->updateOrCreate(
            ['supplier_id' => $supplierId],
            [
                'total_items_count' => (int) ($row->total_items ?? 0),
                'discount_quality_index' => $this->dqiValue($row),
                'indexed_at' => now(),
            ]
        );
    }

    /**
     * @return Collection<int, SupplierRanking>
     */
    public function rankedList(string $sort = self::SORT_ITEMS, int $limit = 50): Collection
    {
        $query = SupplierRanking::query()
            ->with('supplier')
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true));

        if ($sort === self::SORT_DISCOUNT) {
            $query->orderByDesc('discount_quality_index')->orderByDesc('total_items_count');
        } else {
            $query->orderByDesc('total_items_count')->orderByDesc('discount_quality_index');
        }

        return $query->limit($limit)->get();
    }

    /**
     * Single grouped aggregation computing per-supplier active items count and
     * the Discount Quality Index: average of each offer's discount divided by
     * the best active discount across all suppliers for the same product,
     * scaled to 0..100.
     */
    private function aggregateQuery(): Builder
    {
        $bestPerProduct = DB::table('offers')
            ->selectRaw('product_id, MAX(discount) AS max_discount')
            ->where(fn ($q) => $q->where('expires_at', '>', now())->orWhereNull('expires_at'))
            ->groupBy('product_id');

        return DB::table('offers as o')
            ->selectRaw(
                'o.supplier_id,
                 COUNT(*) AS total_items,
                 AVG(
                     CASE
                         WHEN p.max_discount <= 0 THEN 0
                         WHEN o.discount >= p.max_discount THEN 1
                         WHEN o.discount <= 0 THEN 0
                         ELSE (o.discount * 1.0) / p.max_discount
                     END
                 ) * 100 AS dqi'
            )
            ->joinSub($bestPerProduct, 'p', 'p.product_id', '=', 'o.product_id')
            ->where(fn ($q) => $q->where('o.expires_at', '>', now())->orWhereNull('o.expires_at'))
            ->groupBy('o.supplier_id');
    }

    /**
     * @param  object|null  $row
     */
    private function dqiValue($row): ?float
    {
        if (! $row || (int) $row->total_items === 0 || $row->dqi === null) {
            return null;
        }

        return round((float) $row->dqi, 2);
    }
}
