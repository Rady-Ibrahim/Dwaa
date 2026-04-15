<?php

namespace App\Jobs;

use App\Imports\SupplierOfferImport;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Upload;
use App\Services\NormalizerService;
use App\Services\SupplierOfferProductResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row as ExcelRow;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use RuntimeException;
use Throwable;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const NAME_HEADER_ALIASES = [
        'الصنف',
        'اسم الصنف',
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
        'Unit Price',
        'Selling Price',
        'Retail Price',
        'Consumer Price',
        'List Price',
        'Price',
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
        'نسبة الخصم',
        'خصم %',
        'الخصم %',
        '% خصم',
        'خصم تجاري',
        'خصم إضافي',
        'خصم خاص',
        'عرض',
        'العرض',
        'أوفر',
        'بونص',
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

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public Upload $upload) {}

    public function handle(NormalizerService $normalizer, SupplierOfferProductResolver $resolver): void
    {
        $this->upload->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // كتالوج المورد = منتجات بـ supplier_id؛ حذفها يزيل العروض والأسماء البديلة والمفضلات المرتبطة (cascade)
        Product::query()->where('supplier_id', $this->upload->supplier_id)->delete();

        $columnMap = is_array($this->upload->column_map) ? $this->upload->column_map : [];
        $manualMap = $this->hasManualColumnMap($columnMap) ? $columnMap : null;
        $detectedColumnMap = $manualMap;
        $totalRows = 0;
        $matched = 0;

        $path = Storage::disk('local')->path($this->upload->file_path);
        $uploadId = $this->upload->id;
        $supplierId = $this->upload->supplier_id;

        Excel::import(new SupplierOfferImport(function ($row) use (
            $resolver,
            $normalizer,
            $uploadId,
            $supplierId,
            &$totalRows,
            &$matched,
            &$detectedColumnMap
        ) {
            if ($detectedColumnMap === null) {
                $detectedColumnMap = $this->detectColumnMapFromHeader($row);

                if ($detectedColumnMap === null) {
                    return;
                }

                // Skip the detected header row itself.
                return;
            }

            $nameIdx = (int) ($detectedColumnMap['name'] ?? 0);
            $priceIdx = (int) ($detectedColumnMap['price'] ?? 1);
            $rawName = trim((string) $this->cellValueAtColumn($row, $nameIdx));
            $price = (float) $this->cellValueAtColumn($row, $priceIdx);
            $discount = isset($detectedColumnMap['discount'])
                ? (float) $this->cellValueAtColumn($row, (int) $detectedColumnMap['discount'])
                : 0.0;
            $bonus = isset($detectedColumnMap['bonus'])
                ? trim((string) $this->cellValueAtColumn($row, (int) $detectedColumnMap['bonus']))
                : '';

            if ($rawName === '' || $price <= 0) {
                return;
            }

            $totalRows++;

            $normalized = $normalizer->normalize($rawName);
            $product = $resolver->resolve($normalized, $rawName, $supplierId);

            Offer::query()->updateOrCreate(
                [
                    'supplier_id' => $supplierId,
                    'product_id' => $product->id,
                ],
                [
                    'upload_id' => $uploadId,
                    'price' => $price,
                    'discount' => $discount,
                    'bonus' => $bonus !== '' ? $bonus : null,
                    'expires_at' => now()->addDays(7),
                ]
            );
            $matched++;
        }), $path);

        if ($detectedColumnMap === null) {
            throw new RuntimeException('تعذر اكتشاف أعمدة الاسم والسعر من هيدر الملف.');
        }

        $this->upload->update([
            'status' => 'done',
            'total_rows' => $totalRows,
            'matched_count' => $matched,
            'unmatched_count' => 0,
            'finished_at' => now(),
        ]);
    }

    /**
     * @param  array{name?:mixed,price?:mixed,discount?:mixed,bonus?:mixed}  $columnMap
     */
    private function hasManualColumnMap(array $columnMap): bool
    {
        return isset($columnMap['name'], $columnMap['price'])
            && is_numeric($columnMap['name'])
            && is_numeric($columnMap['price']);
    }

    private function detectColumnMapFromHeader(ExcelRow $row): ?array
    {
        $sheetRow = $row->getDelegate();
        $maxColumn = Coordinate::columnIndexFromString($sheetRow->getWorksheet()->getHighestDataColumn($sheetRow->getRowIndex()));
        $aliases = [
            'name' => self::NAME_HEADER_ALIASES,
            'price' => self::PRICE_HEADER_ALIASES,
            'discount' => self::DISCOUNT_HEADER_ALIASES,
        ];
        $map = [];

        for ($column = 1; $column <= $maxColumn; $column++) {
            $header = (string) $sheetRow->getWorksheet()->getCellByColumnAndRow($column, $sheetRow->getRowIndex())->getValue();
            $header = $this->normalizeHeader($header);
            if ($header === '') {
                continue;
            }

            foreach ($aliases as $key => $keyAliases) {
                if (isset($map[$key])) {
                    continue;
                }

                if ($this->headerMatchesAliases($header, $keyAliases)) {
                    $map[$key] = $column - 1;
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
    private function headerMatchesAliases(string $normalizedHeader, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeHeader($alias);
            if ($normalizedAlias === '') {
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

    private function normalizeHeader(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $normalized);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Read by absolute spreadsheet column (0 = A, 1 = B, …). Avoids Row::toArray() indices
     * diverging from user-selected letters under chunked Excel reads.
     */
    private function cellValueAtColumn(ExcelRow $row, int $zeroBasedColumnIndex): mixed
    {
        $spreadsheetRow = $row->getDelegate();
        $columnLetter = Coordinate::stringFromColumnIndex($zeroBasedColumnIndex + 1);
        $address = $columnLetter.$spreadsheetRow->getRowIndex();
        $cell = $spreadsheetRow->getWorksheet()->getCell($address);
        $value = $cell->getValue();

        return $value ?? '';
    }

    public function failed(?Throwable $exception): void
    {
        $this->upload->update([
            'status' => 'failed',
            'error_msg' => $exception?->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
