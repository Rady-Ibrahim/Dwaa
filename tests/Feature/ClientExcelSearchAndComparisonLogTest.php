<?php

namespace Tests\Feature;

use App\Models\ComparisonLog;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ClientExcelSearchAndComparisonLogTest extends TestCase
{
    use RefreshDatabase;

    private function tempXlsxPath(array $rows, string $sheetTitle = 'Sheet'): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $i => $row) {
            foreach ($row as $j => $value) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 1);
                $sheet->setCellValue($col.($i + 1), $value);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'dwaa').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function uploadedFromPath(string $path, string $filename = 'file.xlsx'): UploadedFile
    {
        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_from_excel_bulk_creates_single_summary_search_log(): void
    {
        $path = $this->tempXlsxPath([
            ['الاسم'],
            ['باراسيتامول تجريبي للاختبار الطويل'],
            ['دواء ثانٍ للاختبار الطويل'],
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'subscription_expires_at' => now()->addYear(),
        ]);

        Sanctum::actingAs($user);

        $this->post('/api/search/from-excel', [
            'file' => $this->uploadedFromPath($path, 'list.xlsx'),
            'col_name' => 'A',
            'header_rows' => 1,
            'log_mode' => 'bulk',
        ])->assertOk();

        @unlink($path);

        $this->assertSame(1, SearchLog::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('search_logs', [
            'user_id' => $user->id,
            'source' => SearchLog::SOURCE_EXCEL_BULK,
        ]);
    }

    public function test_from_excel_per_row_logs_each_valid_line(): void
    {
        $path = $this->tempXlsxPath([
            ['الاسم'],
            ['باراسيتامول تجريبي للاختبار الطويل'],
            ['دواء ثانٍ للاختبار الطويل'],
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'subscription_expires_at' => now()->addYear(),
        ]);

        Sanctum::actingAs($user);

        $this->post('/api/search/from-excel', [
            'file' => $this->uploadedFromPath($path, 'list.xlsx'),
            'col_name' => 'A',
            'header_rows' => 1,
            'log_mode' => 'per_row',
        ])->assertOk();

        @unlink($path);

        $this->assertSame(2, SearchLog::query()
            ->where('user_id', $user->id)
            ->where('source', SearchLog::SOURCE_EXCEL_ROW)
            ->count());
    }

    public function test_compare_files_writes_comparison_log(): void
    {
        $pathA = $this->tempXlsxPath([
            ['name', 'price'],
            ['SameDrugName', 10],
        ]);
        $pathB = $this->tempXlsxPath([
            ['name', 'price'],
            ['SameDrugName', 12],
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'subscription_expires_at' => now()->addYear(),
        ]);

        Sanctum::actingAs($user);

        $this->post('/api/compare-files', [
            'file_a' => $this->uploadedFromPath($pathA, 'a.xlsx'),
            'file_b' => $this->uploadedFromPath($pathB, 'b.xlsx'),
            'col_name_a' => 'A',
            'col_price_a' => 'B',
            'col_name_b' => 'A',
            'col_price_b' => 'B',
            'min_similarity' => 80,
        ])->assertOk();

        @unlink($pathA);
        @unlink($pathB);

        $this->assertSame(1, ComparisonLog::query()->where('user_id', $user->id)->count());
        $log = ComparisonLog::query()->where('user_id', $user->id)->first();
        $this->assertSame(1, $log->pairs_count);
    }
}
