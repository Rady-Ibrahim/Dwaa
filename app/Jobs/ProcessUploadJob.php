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
use Throwable;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $columnMap = $this->upload->column_map;
        $totalRows = 0;
        $matched = 0;

        $path = Storage::disk('local')->path($this->upload->file_path);
        $uploadId = $this->upload->id;
        $supplierId = $this->upload->supplier_id;

        Excel::import(new SupplierOfferImport(function ($row) use (
            $resolver,
            $normalizer,
            $columnMap,
            $uploadId,
            $supplierId,
            &$totalRows,
            &$matched
        ) {
            $nameIdx = (int) ($columnMap['name'] ?? 0);
            $priceIdx = (int) ($columnMap['price'] ?? 1);
            $rawName = trim((string) $this->cellValueAtColumn($row, $nameIdx));
            $price = (float) $this->cellValueAtColumn($row, $priceIdx);
            $discount = isset($columnMap['discount'])
                ? (float) $this->cellValueAtColumn($row, (int) $columnMap['discount'])
                : 0.0;
            $bonus = isset($columnMap['bonus'])
                ? trim((string) $this->cellValueAtColumn($row, (int) $columnMap['bonus']))
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

        $this->upload->update([
            'status' => 'done',
            'total_rows' => $totalRows,
            'matched_count' => $matched,
            'unmatched_count' => 0,
            'finished_at' => now(),
        ]);
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
