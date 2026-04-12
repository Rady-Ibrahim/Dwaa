<?php

namespace App\Imports;

use Closure;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;

class SupplierOfferImport implements OnEachRow, WithChunkReading
{
    public function __construct(private \Closure $handler) {}

    public function onRow(Row $row): void
    {
        ($this->handler)($row);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
