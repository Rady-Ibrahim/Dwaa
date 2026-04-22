<?php

namespace App\Services;

use App\Concerns\HasExcelHeaderAliases;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductAlias;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class FileCompareService
{
    use HasExcelHeaderAliases;

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

        $normA = $this->hasManualColumnMap($mapA) ? $this->uploadService->normalizeColumnMap($mapA) : null;
        $normB = $this->hasManualColumnMap($mapB) ? $this->uploadService->normalizeColumnMap($mapB) : null;

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
     * @param  array{name:int,price:int,discount?:int,bonus?:int}|null  $columnIndexes
     * @return list<array{raw_name:string,price:float,discount:float,bonus?:string}>
     */
    private function extractRows(string $absolutePath, ?array $columnIndexes): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $out = [];
        $detectedHeader = $columnIndexes !== null;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! $detectedHeader) {
                $columnIndexes = $this->detectColumnMapFromHeaderRow($row);
                if ($columnIndexes === null) {
                    continue;
                }
                $detectedHeader = true;
                // Skip header row once it is detected.
                continue;
            }

            $nameIdx = $columnIndexes['name'];
            $priceIdx = $columnIndexes['price'];
            $rawName = trim((string) ($row[$nameIdx] ?? ''));
            $price = (float) ($row[$priceIdx] ?? 0);
            $discount = isset($columnIndexes['discount'])
                ? (float) ($row[$columnIndexes['discount']] ?? 0)
                : 0.0;
            $bonus = isset($columnIndexes['bonus'])
                ? trim((string) ($row[$columnIndexes['bonus']] ?? ''))
                : '';

            if ($rawName === '' || $price <= 0) {
                continue;
            }

            $out[] = [
                'raw_name' => $rawName,
                'price' => $price,
                'discount' => $discount,
                'bonus' => $bonus !== '' ? $bonus : null,
            ];
        }

        if (! $detectedHeader) {
            throw new RuntimeException('تعذر اكتشاف أعمدة الاسم والسعر من هيدر ملف المقارنة.');
        }

        return $out;
    }

    /**
     * @param  array{name?:mixed,price?:mixed,discount?:mixed}  $columnMap
     */
    private function hasManualColumnMap(array $columnMap): bool
    {
        return isset($columnMap['name'], $columnMap['price'])
            && $columnMap['name'] !== null
            && $columnMap['price'] !== null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array{name:int,price:int,discount?:int}|null
     */
    private function detectColumnMapFromHeaderRow(array $row): ?array
    {
        $aliases = [
            'name' => self::NAME_HEADER_ALIASES,
            'price' => self::PRICE_HEADER_ALIASES,
            'discount' => self::DISCOUNT_HEADER_ALIASES,
            'bonus' => self::BONUS_HEADER_ALIASES,
        ];
        $map = [];

        foreach ($row as $idx => $value) {
            $header = $this->normalizeHeader((string) $value);
            if ($header === '') {
                continue;
            }

            foreach ($aliases as $key => $keyAliases) {
                if (isset($map[$key])) {
                    continue;
                }

                $isName = ($key === 'name');
                if ($this->headerMatchesAliases($header, $keyAliases, $isName)) {
                    $map[$key] = (int) $idx;
                    break;
                }
            }
        }

        if (! isset($map['name'], $map['price'])) {
            return null;
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $aliases
     */
    private function headerMatchesAliases(string $normalizedHeader, array $aliases, bool $isName = false): bool
    {
        $isLikelyCodeColumn = $this->looksLikeCodeHeader($normalizedHeader);

        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeHeader($alias);
            if ($normalizedAlias === '') {
                continue;
            }

            // يمنع اختيار "رقم الصنف" كعمود اسم منتج
            if ($isName && $isLikelyCodeColumn && in_array($normalizedAlias, ['الصنف', 'item', 'product'], true)) {
                continue;
            }

            if ($normalizedHeader === $normalizedAlias) {
                return true;
            }

            if (mb_strlen($normalizedAlias) >= 4 && str_contains($normalizedHeader, $normalizedAlias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a normalized header looks like a code/ID column
     */
    private function looksLikeCodeHeader(string $normalizedHeader): bool
    {
        return str_contains($normalizedHeader, 'رقم')
            || str_contains($normalizedHeader, 'كود')
            || str_contains($normalizedHeader, 'code')
            || str_contains($normalizedHeader, 'id')
            || str_contains($normalizedHeader, 'sku');
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $normalized);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
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
