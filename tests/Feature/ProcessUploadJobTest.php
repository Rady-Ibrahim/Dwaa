<?php

namespace Tests\Feature;

use App\Jobs\ProcessUploadJob;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Upload;
use App\Services\NormalizerService;
use App\Services\RankingService;
use App\Services\SupplierOfferProductResolver;
use App\Services\XlsxSanitizer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PDOException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProcessUploadJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_upserts_offers_without_deleting_unchanged_rows(): void
    {
        Storage::fake('local');

        $normalizer = app(NormalizerService::class);
        $supplier = Supplier::factory()->create();

        $existing = Product::factory()->create([
            'supplier_id' => $supplier->id,
            'name_ar' => 'باراسيتامول 500',
            'name_en' => null,
            'normalized_name' => $normalizer->normalize('باراسيتامول 500'),
            'phonetic_key' => $normalizer->phoneticConsonantKey('باراسيتامول 500'),
        ]);

        $existingOffer = Offer::query()->create([
            'product_id' => $existing->id,
            'supplier_id' => $supplier->id,
            'upload_id' => null,
            'price' => 10,
            'discount' => 0,
            'expires_at' => now()->addDays(3),
            'is_active' => true,
        ]);

        $stale = Product::factory()->create([
            'supplier_id' => $supplier->id,
            'name_ar' => 'منتج قديم',
            'name_en' => null,
            'normalized_name' => $normalizer->normalize('منتج قديم'),
            'phonetic_key' => $normalizer->phoneticConsonantKey('منتج قديم'),
        ]);

        $staleOffer = Offer::query()->create([
            'product_id' => $stale->id,
            'supplier_id' => $supplier->id,
            'upload_id' => null,
            'price' => 20,
            'discount' => 0,
            'expires_at' => now()->addDays(3),
            'is_active' => true,
        ]);

        $path = 'uploads/offers.xlsx';
        Storage::disk('local')->put($path, $this->xlsxContents([
            ['باراسيتامول 500', 15],
            ['منتج جديد', 30],
        ]));

        $upload = Upload::query()->create([
            'supplier_id' => $supplier->id,
            'file_path' => $path,
            'original_name' => 'offers.xlsx',
            'column_map' => ['name' => 0, 'price' => 1],
            'status' => 'pending',
        ]);

        (new ProcessUploadJob($upload))->handle(
            $normalizer,
            app(SupplierOfferProductResolver::class),
            app(RankingService::class),
            app(XlsxSanitizer::class),
        );

        $upload->refresh();
        $this->assertSame('done', $upload->status);
        $this->assertSame(2, $upload->matched_count);

        $existingOffer->refresh();
        $this->assertSame($upload->id, $existingOffer->upload_id);
        $this->assertSame(15.0, (float) $existingOffer->price);
        $this->assertTrue($existingOffer->is_active);

        $staleOffer->refresh();
        $this->assertFalse($staleOffer->is_active);
        $this->assertDatabaseHas('offers', ['id' => $staleOffer->id]);

        $this->assertSame(3, Product::query()->where('supplier_id', $supplier->id)->count());
        $this->assertDatabaseHas('offers', [
            'supplier_id' => $supplier->id,
            'upload_id' => $upload->id,
            'price' => 30,
            'is_active' => true,
        ]);
    }

    public function test_failed_job_does_not_store_raw_database_errors(): void
    {
        $upload = Upload::factory()->create(['status' => 'processing']);

        $pdo = new PDOException(
            "SQLSTATE[42000]: Syntax error or access violation: 1142 SELECT command denied to user 'app'@'localhost' for table 'secrets'"
        );
        $pdo->errorInfo = ['42000', 1142, 'SELECT command denied'];

        $exception = new QueryException(
            'mysql',
            'SELECT * FROM secrets WHERE id = ?',
            [42],
            $pdo
        );

        (new ProcessUploadJob($upload))->failed($exception);

        $upload->refresh();

        $this->assertSame('failed', $upload->status);
        $this->assertStringNotContainsString('secrets', (string) $upload->error_msg);
        $this->assertStringNotContainsString('SELECT', (string) $upload->error_msg);
        $this->assertStringNotContainsString('1142', (string) $upload->error_msg);
    }

    private function xlsxContents(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
            }
        }

        $temp = tempnam(sys_get_temp_dir(), 'dwaa-xlsx-');
        (new Xlsx($spreadsheet))->save($temp);
        $contents = file_get_contents($temp);
        @unlink($temp);
        $spreadsheet->disconnectWorksheets();

        return $contents;
    }
}
