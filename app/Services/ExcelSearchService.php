<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelSearchService
{
    private const NAME_HEADER_ALIASES = [
        'الصنف',
        'اسم الصنف',
        'اسم الصنف:',
        ':اسم الصنف',
        'إسم الصنف ',
        'اسم المنتج',
        'اسم الصنف / المنتج',
        'المنتج',
        'بيان',
        'البيان',
        'الوصف',
        'اسم',
        'اسم المادة',
        'اسم الدواء',
        'الصنف بالكامل',
        'Item',
        'Item Name',
        'Product',
        'Product Name',
        'PROD_NAME',
        'PRODUCT_NAME',
        'Description',
        'Trade Name',
        'Commercial Name',
        'Brand Name',
        'Generic Name',
        'Medicine Name',
    ];
    private const PRICE_HEADER_ALIASES = [
        'سعر',
        'السعر',
        ':السعر',
        'سعر ج',
        'سعر البيع',
        'سعر الوحدة',
        'سعر المستهلك',
        'السعر النهائي',
        'سعر قبل الخصم',
        'سعر العبوة',
        'سعر الكرتونة',
        'سعر القطاعي',
        'سعر الجملة',
        'سعر خاص',
        'Public Price',
        'Price',
        'PRICE_1',
        'Unit Price',
        'Selling Price',
        'Retail Price',
        'Consumer Price',
        'List Price',
        'Base Price',
        'Original Price',
        'Gross Price',
        'MRP',
        'PTR',
        'PTD',
    ];
    private const DISCOUNT_HEADER_ALIASES = [
        'خصم',
        'الخصم',
        ':الخصم',
        'الخصم:',
        'نسبة الخصم',
        'خصم %',
        'الخصم %',
        '% خصم',
        'خصم تجاري',
        'خصم إضافي',
        'الخصم اساسى :',
        'خصم خاص',
        'عرض',
        'العرض',
        'أوفر',
        'بونص',
        'مندوب',
        'المندوب',
        'شركات',
        'جمله',
        'جملة',
        'صيدليات',
        'صيدلية',
        'الموزع',
        'الموزعين',
        'Discount',
        'Discount %',
        'Discount-%',
        'Disc',
        'Disc %',
        'Promo',
        'Promotion',
        'Offer',
        'Deal',
        'Rebate',
        'Markdown',
    ];

    public function __construct(private UploadService $uploadService) {}

    /**
     * @return list<string>
     */
    public function readNameColumn(string $absolutePath, string $colNameLetter, int $headerRowsToSkip): array
    {
        $nameIdx = $this->uploadService->toColumnIndex($colNameLetter);
        $spreadsheet = IOFactory::load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $out = [];

        foreach ($rows as $i => $row) {
            if ($i < $headerRowsToSkip) {
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row[$nameIdx] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = $name;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function readNameColumnAuto(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $headerIndex = null;
        $nameIdx = null;

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $detected = $this->detectNameColumnFromHeaderRow($row);
            if ($detected !== null) {
                $headerIndex = $i;
                $nameIdx = $detected;
                break;
            }
        }

        if ($nameIdx === null || $headerIndex === null) {
            return [];
        }

        $out = [];
        foreach ($rows as $i => $row) {
            if ($i <= $headerIndex || ! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row[$nameIdx] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = $name;
        }

        return $out;
    }

    /**
     * @return list<array{name:string,price:?float,discount:?float}>
     */
    public function readRowsAutoForPlatformCompare(string $absolutePath, int $maxRows = 200): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $headerIndex = null;
        $map = null;

        // Find header row first
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $detected = $this->detectCompareColumnsFromHeaderRow($row);
            if ($detected !== null) {
                $headerIndex = $i;
                $map = $detected;
                break;
            }
        }

        if ($headerIndex === null || $map === null) {
            return [];
        }

        // Validate column detection by checking sample data
        $sampleValid = $this->validateColumnDetection($rows, $headerIndex, $map);
        if (!$sampleValid) {
            return [];
        }

        $out = [];
        foreach ($rows as $i => $row) {
            if ($i <= $headerIndex || ! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row[$map['name']] ?? ''));

            // Skip rows with empty or too short product names
            if ($name === '' || strlen($name) < 3) {
                continue;
            }

            // Skip rows where name is just numbers (likely mis-detected column)
            if (preg_match('/^\d+(\.\d+)?$/', $name)) {
                continue;
            }

            // Skip rows where name starts with numbers followed by space and very short text
            // This catches cases like "150 ابي" which are likely column mismatch
            if (preg_match('/^\d+\s+\S{1,3}$/', $name)) {
                continue;
            }

            $priceRaw = $row[$map['price']] ?? null;
            $discountRaw = isset($map['discount']) ? ($row[$map['discount']] ?? null) : null;

            $out[] = [
                'name' => $name,
                'price' => is_numeric($priceRaw) ? (float) $priceRaw : null,
                'discount' => is_numeric($discountRaw) ? (float) $discountRaw : null,
            ];

            if (count($out) >= $maxRows) {
                break;
            }
        }

        return $out;
    }

    /**
     * Validate that detected columns contain reasonable data
     */
    private function validateColumnDetection(array $rows, int $headerIndex, array $map): bool
    {
        // Check first 3-5 data rows to validate column detection
        $validSamples = 0;
        $sampleSize = 0;

        foreach ($rows as $i => $row) {
            if ($i <= $headerIndex || ! is_array($row) || $sampleSize >= 5) {
                continue;
            }
            if (!is_array($row)) continue;

            $sampleSize++;
            $name = trim((string) ($row[$map['name']] ?? ''));
            $price = $row[$map['price']] ?? null;

            // Valid sample: has a reasonable name (3+ chars not all numbers) and numeric price
            if (
                strlen($name) >= 3 &&
                !preg_match('/^\d+(\.\d+)?$/', $name) &&
                is_numeric($price) &&
                (float)$price > 0
            ) {
                $validSamples++;
            }
        }

        // At least 50% of samples should be valid
        return $sampleSize === 0 || ($validSamples / $sampleSize) >= 0.5;
    }

    /**
     * @return array{name:int,price:int,discount?:int}|null
     */
    private function detectCompareColumnsFromHeaderRow(array $row): ?array
    {
        $map = [];
        foreach ($row as $idx => $value) {
            $header = $this->normalizeHeader((string) $value);
            if ($header === '') {
                continue;
            }

            if (! isset($map['name']) && $this->headerMatchesAliases($header, self::NAME_HEADER_ALIASES, true)) {
                $map['name'] = (int) $idx;
                continue;
            }
            if (! isset($map['price']) && $this->headerMatchesAliases($header, self::PRICE_HEADER_ALIASES, false)) {
                $map['price'] = (int) $idx;
                continue;
            }
            if (! isset($map['discount']) && $this->headerMatchesAliases($header, self::DISCOUNT_HEADER_ALIASES, false)) {
                $map['discount'] = (int) $idx;
            }
        }

        if (! isset($map['name'], $map['price'])) {
            return null;
        }

        return $map;
    }

    /**
     * @param list<string> $aliases
     */
    private function headerMatchesAliases(string $header, array $aliases, bool $isName): bool
    {
        $isLikelyCodeColumn = $this->looksLikeCodeHeader($header);
        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeHeader($alias);
            if ($normalizedAlias === '') {
                continue;
            }
            if ($isName && $isLikelyCodeColumn && in_array($normalizedAlias, ['الصنف', 'item', 'product'], true)) {
                continue;
            }
            if ($header === $normalizedAlias) {
                return true;
            }
            if (mb_strlen($normalizedAlias) >= 4 && str_contains($header, $normalizedAlias)) {
                return true;
            }
        }

        return false;
    }

    private function detectNameColumnFromHeaderRow(array $row): ?int
    {
        $aliases = [
            'الصنف',
            'اسم الصنف',
            'اسم الصنف:',
            ':اسم الصنف',
            'إسم الصنف ',
            'اسم المنتج',
            'اسم الصنف / المنتج',
            'المنتج',
            'بيان',
            'البيان',
            'الوصف',
            'اسم',
            'اسم المادة',
            'اسم الدواء',
            'الصنف بالكامل',
            'Item',
            'Item Name',
            'Product',
            'Product Name',
            'PROD_NAME',
            'PRODUCT_NAME',
            'Description',
            'Trade Name',
            'Commercial Name',
            'Brand Name',
            'Generic Name',
            'Medicine Name',
        ];

        foreach ($row as $idx => $value) {
            $header = $this->normalizeHeader((string) $value);
            if ($header === '') {
                continue;
            }

            $isLikelyCodeColumn = $this->looksLikeCodeHeader($header);
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizeHeader($alias);
                if ($normalizedAlias === '') {
                    continue;
                }

                if ($isLikelyCodeColumn && in_array($normalizedAlias, ['الصنف', 'item', 'product'], true)) {
                    continue;
                }

                if ($header === $normalizedAlias) {
                    return (int) $idx;
                }

                if (mb_strlen($normalizedAlias) >= 4 && str_contains($header, $normalizedAlias)) {
                    return (int) $idx;
                }
            }
        }

        return null;
    }

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
}
