<?php

namespace App\Services;

use App\Jobs\ProcessUploadJob;
use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function storeUpload(int $supplierId, UploadedFile $file, array $columnMap): Upload
    {
        $path = $file->store('uploads/'.now()->format('Y/m'), 'local');

        $upload = Upload::query()->create([
            'supplier_id' => $supplierId,
            'file_path' => $path,
            'column_map' => $this->normalizeColumnMap($columnMap),
            'status' => 'pending',
        ]);

        ProcessUploadJob::dispatch($upload);

        return $upload;
    }

    /**
     * Convert column letters (A, B, AA) to 0-based indices for row arrays.
     *
     * @param  array{name?:mixed,price?:mixed,discount?:mixed,bonus?:mixed}  $columnMap
     * @return array{name:int,price:int,discount?:int,bonus?:int}
     */
    public function normalizeColumnMap(array $columnMap): array
    {
        $out = [];
        foreach (['name', 'price', 'discount', 'bonus'] as $key) {
            if (! isset($columnMap[$key])) {
                continue;
            }
            $out[$key] = $this->toColumnIndex($columnMap[$key]);
        }

        return $out;
    }

    /**
     * @param  string|int  $value
     */
    public function toColumnIndex(string|int $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        $letters = strtoupper(trim((string) $value));

        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($letters) - 1;
    }
}
