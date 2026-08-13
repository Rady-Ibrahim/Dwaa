<?php

namespace Tests\Feature;

use App\Models\Upload;
use App\Models\Supplier;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadsPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_upload_deletes_physical_old_files_but_keeps_db_records()
    {
        Storage::fake('local');

        $supplier = Supplier::factory()->create();

        $oldPath = 'uploads/old/oldfile.xlsx';
        Storage::disk('local')->put($oldPath, 'old');

        $old = Upload::query()->create([
            'supplier_id' => $supplier->id,
            'file_path' => $oldPath,
            'status' => 'done',
            'column_map' => [],
            'finished_at' => now()->subDays(10),
        ]);

        $processingPath = 'uploads/processing/file.xlsx';
        Storage::disk('local')->put($processingPath, 'processing');
        $processing = Upload::query()->create([
            'supplier_id' => $supplier->id,
            'file_path' => $processingPath,
            'status' => 'processing',
            'column_map' => [],
        ]);

        Bus::fake();
        $service = app(UploadService::class);

        $file = UploadedFile::fake()->create('new.xlsx');
        $newUpload = $service->storeUpload($supplier->id, $file, []);

        // old physical file should be deleted
        $this->assertFalse(Storage::disk('local')->exists($oldPath));

        // DB record should still exist
        $this->assertNotNull(Upload::query()->find($old->id));

        // processing file should NOT be deleted
        $this->assertTrue(Storage::disk('local')->exists($processingPath));
    }

    public function test_prune_old_command_deletes_files_and_db_records_older_than_days()
    {
        Storage::fake('local');

        $supplier = Supplier::factory()->create();

        $veryOldPath = 'uploads/very_old/file.xlsx';
        Storage::disk('local')->put($veryOldPath, 'x');

        $veryOld = Upload::query()->create([
            'supplier_id' => $supplier->id,
            'file_path' => $veryOldPath,
            'status' => 'done',
            'column_map' => [],
            'finished_at' => now()->subDays(40),
        ]);

        $recentPath = 'uploads/recent/file.xlsx';
        Storage::disk('local')->put($recentPath, 'y');
        $recent = Upload::query()->create([
            'supplier_id' => $supplier->id,
            'file_path' => $recentPath,
            'status' => 'done',
            'column_map' => [],
            'finished_at' => now()->subDays(10),
        ]);

        Artisan::call('uploads:prune-old', ['--days' => 30]);

        $this->assertFalse(Storage::disk('local')->exists($veryOldPath));
        $this->assertNull(Upload::query()->find($veryOld->id));

        $this->assertTrue(Storage::disk('local')->exists($recentPath));
        $this->assertNotNull(Upload::query()->find($recent->id));
    }
}
