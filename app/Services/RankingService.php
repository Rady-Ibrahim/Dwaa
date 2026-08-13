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

    private ?int $winnableCount = null;

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
     * the Discount Quality Index.
     *
     * المنتجات مربوطة بكل مورد على حدة (منتج الشيت = مورد واحد)، لذلك يتم احتساب
     * "أعلى خصم" على مستوى الاسم المُطبّع (normalized_name) عبر كل الموردين،
     * وعدد منتجات المورد = عدد الأسماء المُطبّعة التي يملك فيها أعلى خصم،
     * والمؤشر = (عددها / إجمالي الأسماء التي عليها خصم) × 100.
     */
    private function aggregateQuery(): Builder
    {
        $bestPerName = DB::table('offers as o')
            ->join('products as p', 'p.id', '=', 'o.product_id')
            ->selectRaw('p.normalized_name AS name, MAX(o.discount) AS max_discount')
            ->where(fn ($q) => $q->where('o.expires_at', '>', now())->orWhereNull('o.expires_at'))
            ->groupBy('p.normalized_name');

        return DB::table('offers as o')
            ->join('products as p', 'p.id', '=', 'o.product_id')
            ->selectRaw(
                'o.supplier_id,
                 COUNT(*) AS total_items,
                 SUM(
                     CASE
                         WHEN bn.max_discount > 0 AND o.discount >= bn.max_discount THEN 1
                         ELSE 0
                     END
                 ) AS best_items'
            )
            ->joinSub($bestPerName, 'bn', 'bn.name', '=', 'p.normalized_name')
            ->where(fn ($q) => $q->where('o.expires_at', '>', now())->orWhereNull('o.expires_at'))
            ->groupBy('o.supplier_id');
    }

    /**
     * @param  object|null  $row
     */
    private function dqiValue($row): ?float
    {
        if (! $row || (int) $row->total_items === 0 || $row->best_items === null) {
            return null;
        }

        $total = $this->winnableProductCount();

        if ($total <= 0) {
            return null;
        }

        return round(((float) $row->best_items / $total) * 100, 2);
    }

    /**
     * إجمالي الأسماء المُطبّعة الفعّالة التي يوجد عليها خصم > 0 (يمكن "الفوز" بها).
     */
    private function winnableProductCount(): int
    {
        if ($this->winnableCount !== null) {
            return $this->winnableCount;
        }

        $bestPerName = DB::table('offers as o')
            ->join('products as p', 'p.id', '=', 'o.product_id')
            ->selectRaw('p.normalized_name AS name, MAX(o.discount) AS max_discount')
            ->where(fn ($q) => $q->where('o.expires_at', '>', now())->orWhereNull('o.expires_at'))
            ->groupBy('p.normalized_name');

        return $this->winnableCount = (int) DB::table($bestPerName)
            ->where('max_discount', '>', 0)
            ->count();
    }
}
