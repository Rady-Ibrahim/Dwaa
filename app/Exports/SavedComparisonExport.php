<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class SavedComparisonExport
{
    private const COLOR_DARK_BLUE = '0B2545';
    private const COLOR_BORDER    = 'D3D3D3';
    private const COLOR_ROW_ALT   = 'F2F6FC';

    private const FILE_COMPARE_HEADINGS = [
        '# مسلسل',
        'الصنف',
        'سعر الملف الأول',
        'سعر الملف الثاني',
        'خصم الملف الأول',
        'خصم الملف الثاني',
        'نسبة التشابه %',
    ];

    private const PLATFORM_COMPARE_HEADINGS = [
        '# مسلسل',
        'الصنف من الملف',
        'سعر الملف',
        'خصم الملف',
        'الصنف المطابق',
        'التشابه %',
        'أفضل مورد',
        'سعر المنصة',
        'خصم المنصة',
        'فرق السعر',
        'فرق الخصم',
    ];

    private const STATUS_LABELS = [
        'both'     => 'مطابق',
        'no_match' => 'بدون مطابقة',
        'skipped'  => 'مُتخطّى',
        'only_a'   => 'في الملف فقط',
        'only_b'   => 'في المنصة فقط',
    ];

    private string $type;
    private array  $data;
    private string $title;

    public function __construct(string $type, array $data, string $title = 'Med RANKO')
    {
        $this->type  = $type;
        $this->data  = $data;
        $this->title = $title;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('Med RANKO Saved Compare');

        if ($this->type === 'platform_compare') {
            $this->buildPlatformCompare($sheet);
        } else {
            $this->buildFileCompare($sheet);
        }

        return $spreadsheet;
    }

    private function buildPlatformCompare(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $colCount  = count(self::PLATFORM_COMPARE_HEADINGS);
        $lastCol   = Coordinate::stringFromColumnIndex($colCount);
        $headRow   = 3;
        $dataStart = 4;

        $this->writeBanner($sheet, $lastCol);
        $this->writeHeadings($sheet, self::PLATFORM_COMPARE_HEADINGS, $headRow, $colCount);

        $rowNum = 0;
        foreach ($this->data as $line) {
            $rowNum++;
            $row  = $dataStart + $rowNum - 1;
            $best = $line['platform_best'] ?? null;

            $bestSupplier = is_array($best) ? ($best['supplier'] ?? '-') : '-';
            $bestPrice    = is_array($best) ? ($best['price'] ?? null) : null;
            $bestDiscount = is_array($best) ? ($best['discount'] ?? null) : null;

            $priceDiff  = ($line['price'] ?? null) !== null && $bestPrice !== null
                ? round((float) $line['price'] - (float) $bestPrice, 2) : null;
            $discountDiff = ($line['discount'] ?? null) !== null && $bestDiscount !== null
                ? round(((float) $line['discount'] - (float) $bestDiscount) / 100, 4) : null;

            $sheet->setCellValue("A{$row}", $rowNum);
            $sheet->setCellValue("B{$row}", $this->safeUtf8($line['query'] ?? '-'));
            $sheet->setCellValue("C{$row}", $line['price'] ?? null);
            $sheet->setCellValue("D{$row}", $this->toPercentValue($line['discount']));
            $sheet->setCellValue("E{$row}", $this->safeUtf8($line['matched_product'] ?? '-'));
            $sheet->setCellValue("F{$row}", $line['similarity'] ?? 0);
            $sheet->setCellValue("G{$row}", $this->safeUtf8($bestSupplier));
            $sheet->setCellValue("H{$row}", $bestPrice);
            $sheet->setCellValue("I{$row}", $this->toPercentValue($bestDiscount));
            $sheet->setCellValue("J{$row}", $priceDiff);
            $sheet->setCellValue("K{$row}", $discountDiff);
        }

        $this->applyAutoFilter($sheet, $lastCol, $headRow);
        $this->applyDataStyles($sheet, $dataStart, $colCount, $headRow, $lastCol, 'platform');
    }

    private function buildFileCompare(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $colCount  = count(self::FILE_COMPARE_HEADINGS);
        $lastCol   = Coordinate::stringFromColumnIndex($colCount);
        $headRow   = 3;
        $dataStart = 4;

        $this->writeBanner($sheet, $lastCol);
        $this->writeHeadings($sheet, self::FILE_COMPARE_HEADINGS, $headRow, $colCount);

        $rowNum = 0;
        foreach ($this->data as $pair) {
            $rowNum++;
            $row = $dataStart + $rowNum - 1;

            $sheet->setCellValue("A{$row}", $rowNum);
            $sheet->setCellValue("B{$row}", $this->safeUtf8($pair['file_a']['raw_name'] ?? '-'));
            $sheet->setCellValue("C{$row}", $pair['file_a']['price'] ?? null);
            $sheet->setCellValue("D{$row}", $pair['file_b']['price'] ?? null);
            $sheet->setCellValue("E{$row}", $this->toPercentValue($pair['file_a']['discount'] ?? null));
            $sheet->setCellValue("F{$row}", $this->toPercentValue($pair['file_b']['discount'] ?? null));
            $sheet->setCellValue("G{$row}", $pair['similarity_percent'] ?? 0);
        }

        $this->applyAutoFilter($sheet, $lastCol, $headRow);
        $this->applyDataStyles($sheet, $dataStart, $colCount, $headRow, $lastCol, 'file');
    }

    private function writeBanner(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $lastCol): void
    {
        $range = "A1:{$lastCol}2";
        $sheet->mergeCells($range);
        $sheet->setCellValue('A1', 'Med RANKO');
        $sheet->getStyle($range)->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_DARK_BLUE]],
            'font'      => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(24);
    }

    private function writeHeadings(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $headings,
        int $headRow,
        int $colCount,
    ): void {
        foreach ($headings as $colIdx => $heading) {
            $cellRef = Coordinate::stringFromColumnIndex($colIdx + 1) . $headRow;
            $sheet->setCellValue($cellRef, $heading);
        }

        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle("A{$headRow}:{$lastCol}{$headRow}")->applyFromArray([
            'fill'       => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_DARK_BLUE]],
            'font'       => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'alignment'  => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'    => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);
        $sheet->getRowDimension($headRow)->setRowHeight(36);
    }

    private function applyAutoFilter(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $lastCol,
        int $headRow,
    ): void {
        $sheet->setAutoFilter("A{$headRow}:{$lastCol}{$headRow}");
    }

    private function applyDataStyles(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $dataStart,
        int $colCount,
        int $headRow,
        string $lastCol,
        string $mode,
    ): void {
        $lastRow = $sheet->getHighestRow();
        if ($lastRow < $dataStart) {
            return;
        }

        $dataRange = "A{$dataStart}:{$lastCol}{$lastRow}";
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
            'font'    => ['name' => 'Arial', 'size' => 10],
        ]);

        for ($row = $dataStart; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->applyFromArray([
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_ROW_ALT],
                ]);
            }

            for ($c = 1; $c <= $colCount; $c++) {
                $sheet->getCellByColumnAndRow($c, $row)->getStyle()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }

            if ($mode === 'platform') {
                foreach ([3, 8, 10] as $c) {
                    $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                foreach ([4, 9, 11] as $c) {
                    $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()->setFormatCode('0.0%');
                }
                $sheet->getStyleByColumnAndRow(6, $row)->getNumberFormat()->setFormatCode('0.0');
            } else {
                foreach ([3, 4] as $c) {
                    $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                foreach ([5, 6] as $c) {
                    $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()->setFormatCode('0.0%');
                }
                $sheet->getStyleByColumnAndRow(7, $row)->getNumberFormat()->setFormatCode('0.0');
            }
        }

        $colWidths = $mode === 'platform'
            ? ['A' => 10, 'B' => 38, 'C' => 14, 'D' => 12, 'E' => 38, 'F' => 12, 'G' => 28, 'H' => 14, 'I' => 12, 'J' => 14, 'K' => 12]
            : ['A' => 10, 'B' => 38, 'C' => 14, 'D' => 14, 'E' => 12, 'F' => 12, 'G' => 14];

        foreach ($colWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }

    private function toPercentValue($value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value / 100;
    }

    public function safeUtf8(?string $str): string
    {
        if ($str === null || $str === '') {
            return '';
        }

        try {
            $cleaned = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            if (mb_check_encoding($cleaned, 'UTF-8')) {
                return $cleaned;
            }
        } catch (\Throwable $e) {
            // Fall through
        }

        $iconvCleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $str);

        return $iconvCleaned !== false ? $iconvCleaned : '';
    }
}
