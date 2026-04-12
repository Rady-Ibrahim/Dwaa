<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductAlias;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileCompareService
{
    public function __construct(
        private NormalizerService $normalizer,
        private UploadService $uploadService,
    ) {}

    /**
     * @param  array{name:string,price:string,discount?:string}  $mapA  column letters or indices
     * @param  array{name:string,price:string,discount?:string}  $mapB
     * @return array{pairs: list<array<string,mixed>>, unmatched_a: list<array<string,mixed>>, unmatched_b: list<array<string,mixed>>}
     */
    public function compareUploadedFiles(
        string $storagePathA,
        string $storagePathB,
        array $mapA,
        array $mapB,
        float $minSimilarityPercent = 80.0
    ): array {
        $pathA = Storage::disk('local')->path($storagePathA);
        $pathB = Storage::disk('local')->path($storagePathB);

        $normA = $this->uploadService->normalizeColumnMap($mapA);
        $normB = $this->uploadService->normalizeColumnMap($mapB);

        $rowsA = $this->extractRows($pathA, $normA);
        $rowsB = $this->extractRows($pathB, $normB);

        $usedB = [];
        $pairs = [];
        $unmatchedA = [];

        foreach ($rowsA as $a) {
            $nA = $this->normalizer->normalize($a['raw_name']);
            $bestJ = null;
            $bestPct = 0.0;

            foreach ($rowsB as $j => $b) {
                if (isset($usedB[$j])) {
                    continue;
                }
                $nB = $this->normalizer->normalize($b['raw_name']);
                similar_text($nA, $nB, $pct);
                if ($pct >= $minSimilarityPercent && $pct > $bestPct) {
                    $bestPct = $pct;
                    $bestJ = $j;
                }
            }

            if ($bestJ !== null) {
                $usedB[$bestJ] = true;
                $b = $rowsB[$bestJ];
                $product = $this->findProduct($nA) ?? $this->findProduct($this->normalizer->normalize($b['raw_name']));
                $pairs[] = [
                    'file_a' => $a,
                    'file_b' => $b,
                    'similarity_percent' => round($bestPct, 1),
                    'platform' => $this->platformSummary($product),
                ];
            } else {
                $unmatchedA[] = $a;
            }
        }

        $unmatchedB = [];
        foreach ($rowsB as $j => $b) {
            if (! isset($usedB[$j])) {
                $unmatchedB[] = $b;
            }
        }

        return [
            'pairs' => $pairs,
            'unmatched_a' => $unmatchedA,
            'unmatched_b' => $unmatchedB,
        ];
    }

    /**
     * @param  array{name:int,price:int,discount?:int}  $columnIndexes
     * @return list<array{raw_name:string,price:float,discount:float}>
     */
    private function extractRows(string $absolutePath, array $columnIndexes): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $out = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $nameIdx = $columnIndexes['name'];
            $priceIdx = $columnIndexes['price'];
            $rawName = trim((string) ($row[$nameIdx] ?? ''));
            $price = (float) ($row[$priceIdx] ?? 0);
            $discount = isset($columnIndexes['discount'])
                ? (float) ($row[$columnIndexes['discount']] ?? 0)
                : 0.0;

            if ($rawName === '' || $price <= 0) {
                continue;
            }

            $out[] = [
                'raw_name' => $rawName,
                'price' => $price,
                'discount' => $discount,
            ];
        }

        return $out;
    }

    private function findProduct(string $normalized): ?Product
    {
        $p = Product::query()->where('normalized_name', $normalized)->first();
        if ($p) {
            return $p;
        }

        $alias = ProductAlias::query()->where('normalized_name', $normalized)->with('product')->first();

        return $alias?->product;
    }

    /**
     * @return array<string, mixed>
     */
    private function platformSummary(?Product $product): array
    {
        if (! $product) {
            return [
                'matched' => false,
                'message' => 'الصنف غير موجود في قاعدة المنصة',
            ];
        }

        $offers = Offer::query()
            ->where('product_id', $product->id)
            ->active()
            ->get();

        return [
            'matched' => true,
            'product_id' => $product->id,
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'code' => $product->code,
            'suppliers_count' => $offers->count(),
            'lowest_price' => $offers->min('price') !== null ? (float) $offers->min('price') : null,
            'highest_discount' => $offers->max('discount') !== null ? (float) $offers->max('discount') : null,
        ];
    }
}
