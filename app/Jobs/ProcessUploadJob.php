<?php

namespace App\Jobs;

use App\Concerns\HasExcelHeaderAliases;
use App\Imports\SupplierOfferImport;
use App\Models\Offer;
use App\Models\Upload;
use App\Services\NormalizerService;
use App\Services\RankingService;
use App\Services\SupplierOfferProductResolver;
use App\Services\XlsxSanitizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row as ExcelRow;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HasExcelHeaderAliases;

    public int $tries = 3;

    public int $timeout = 300;

    private const HEADER_SCAN_ROW_LIMIT = 50;

    public function __construct(public Upload $upload) {}

    public function handle(NormalizerService $normalizer, SupplierOfferProductResolver $resolver, RankingService $rankingService, XlsxSanitizer $sanitizer): void
    {
        $this->upload->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $columnMap = is_array($this->upload->column_map) ? $this->upload->column_map : [];
        $manualMap = $this->hasManualColumnMap($columnMap) ? $columnMap : null;
        $totalRows = 0;
        $matched = 0;

        $sourcePath = Storage::disk('local')->path($this->upload->file_path);
        // بعض الملفات تُنتج بتواريخ عربية في بياناتها الداخلية وتعطّل قراءة Excel.
        $path = $sanitizer->prepareForReading($sourcePath);

        try {
            $this->processFile($path, $manualMap, $normalizer, $resolver, $totalRows, $matched);

            $this->upload->update([
                'status' => 'done',
                'total_rows' => $totalRows,
                'matched_count' => $matched,
                'unmatched_count' => 0,
                'finished_at' => now(),
            ]);
        } finally {
            $sanitizer->cleanup($path);
        }

        try {
            Cache::forget('platform_compare_products_cache_v3');
        } catch (Throwable) {
            // Cache driver failure should never block the upload process.
        }

        $rankingService->recalculateForSupplier($this->upload->supplier_id);
    }

    private function processFile(string $path, ?array $manualMap, NormalizerService $normalizer, SupplierOfferProductResolver $resolver, int &$totalRows, int &$matched): void
    {
        // اكتشاف الأعمدة قبل أي حذف، حتى لا تفقد عروض المورد القديمة عند فشل الملف.
        $detection = $manualMap !== null
            ? ['map' => $manualMap, 'header_row' => null]
            : $this->detectColumnMapFromFile($path);

        if ($detection === null) {
            throw new RuntimeException('تعذر اكتشاف أعمدة الاسم والسعر من هيدر الملف.');
        }

        $detectedColumnMap = $detection['map'];
        $headerRowIndex = $detection['header_row'];

        // حذف العروض القديمة فقط بعد التأكد من صلاحية الملف، مع الاحتفاظ بالمنتجات.
        Offer::query()->where('supplier_id', $this->upload->supplier_id)->delete();

        $uploadId = $this->upload->id;
        $supplierId = $this->upload->supplier_id;

        Excel::import(new SupplierOfferImport(function ($row) use (
            $resolver,
            $normalizer,
            $uploadId,
            $supplierId,
            $detectedColumnMap,
            $headerRowIndex,
            &$totalRows,
            &$matched
        ) {
            if ($headerRowIndex !== null && $row->getDelegate()->getRowIndex() === $headerRowIndex) {
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

    /**
     * @return array{map: array<string, int>, header_row: int|null}|null
     */
    private function detectColumnMapFromFile(string $path): ?array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'listWorksheetInfo')) {
            $sheets = $reader->listWorksheetInfo($path);
            $firstSheet = $sheets[0]['worksheetName'] ?? null;

            if ($firstSheet !== null && method_exists($reader, 'setLoadSheetsOnly')) {
                // Excel::import يعالج أول شيت فقط، لذا يجب أن يكون الفحص على نفس الشيت.
                $reader->setLoadSheetsOnly([$firstSheet]);
            }
        }

        $scanLimit = self::HEADER_SCAN_ROW_LIMIT;
        $reader->setReadFilter(new class($scanLimit) implements IReadFilter {
            public function __construct(private int $maxRows) {}

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->maxRows;
            }
        });

        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getSheet(0);

        try {
            $highestRow = min($scanLimit, (int) $worksheet->getHighestDataRow());

            for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                $map = $this->detectColumnMapOnRow($worksheet, $rowIndex);

                if ($map !== null) {
                    return ['map' => $map, 'header_row' => $rowIndex];
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return null;
    }

    private function detectColumnMapOnRow(Worksheet $worksheet, int $rowIndex): ?array
    {
        $maxColumn = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn($rowIndex));
        $aliases = [
            'name' => self::NAME_HEADER_ALIASES,
            'price' => self::PRICE_HEADER_ALIASES,
            'discount' => self::DISCOUNT_HEADER_ALIASES,
            'bonus' => self::BONUS_HEADER_ALIASES,
        ];
        $map = [];

        for ($column = 1; $column <= $maxColumn; $column++) {
            $header = (string) $worksheet->getCellByColumnAndRow($column, $rowIndex)->getValue();
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
        $isLikelyCodeColumn = $this->looksLikeCodeHeader($normalizedHeader);

        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeHeader($alias);
            if ($normalizedAlias === '') {
                continue;
            }

            // يمنع اختيار "رقم الصنف" كعمود اسم منتج.
            if ($isLikelyCodeColumn && in_array($normalizedAlias, ['الصنف', 'item', 'product'], true)) {
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
        $normalized = str_replace(['ى', 'ي'], 'ي', $normalized);
        $normalized = str_replace(['ة', 'ه'], 'ه', $normalized);
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
        // رسائل الأخطاء قد تحوي بايتات UTF-8 غير صالحة (من أسماء/بيانات الملفات) ترفضها قاعدة البيانات.
        $message = $exception?->getMessage() ?? 'خطأ غير معروف أثناء معالجة الملف.';
        $message = trim(mb_convert_encoding($message, 'UTF-8', 'UTF-8'));
        $message = mb_substr($message !== '' ? $message : 'خطأ غير معروف أثناء معالجة الملف.', 0, 1000);

        try {
            $this->upload->update([
                'status' => 'failed',
                'error_msg' => $message,
                'finished_at' => now(),
            ]);
        } catch (Throwable) {
            $this->upload->forceFill([
                'status' => 'failed',
                'error_msg' => null,
                'finished_at' => now(),
            ])->save();
        }
    }
}
