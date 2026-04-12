<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelSearchService
{
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
}
