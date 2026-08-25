<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class PlatformCompareExport
{
    private const COLOR_DARK_BLUE = '0B2545';
    private const COLOR_BORDER    = 'D3D3D3';
    private const COLOR_ROW_ALT   = 'F2F6FC';

    private const HEADINGS = [
        '# مسلسل',
        'المنتج (من الملف)',
        'المنتج (من المنصة)',
        'نسبة المطابقة %',
        'سعر الملف',
        'خصم الملف',
        'أفضل مورد',
        'سعر المنصة',
        'خصم المنصة',
        'فرق السعر',
        'فرق الخصم',
        'حالة المطابقة',
    ];

    private const STATUS_LABELS = [
        'both'     => 'مطابق',
        'no_match' => 'بدون مطابقة',
        'skipped'  => 'مُتخطّى',
        'only_a'   => 'في الملف فقط',
        'only_b'   => 'في المنصة فقط',
    ];

    private const COL_COUNT = 12;

    private const HEAD_ROW = 3;

    private const DATA_START_ROW = 4;

    private array $lines;

    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('Med RANKO Compare');

        $lastCol = Coordinate::stringFromColumnIndex(self::COL_COUNT);

        $this->writeBanner($sheet, $lastCol);
        $this->writeHeadings($sheet);
        $this->writeDataRows($sheet);
        $this->applyAutoFilter($sheet, $lastCol);
        $this->applyColumnWidths($sheet);

        return $spreadsheet;
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

    private function writeHeadings(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        foreach (self::HEADINGS as $colIdx => $heading) {
            $cellRef = Coordinate::stringFromColumnIndex($colIdx + 1) . self::HEAD_ROW;
            $sheet->setCellValue($cellRef, $heading);
        }

        $lastCol = Coordinate::stringFromColumnIndex(self::COL_COUNT);
        $sheet->getStyle("A" . self::HEAD_ROW . ":{$lastCol}" . self::HEAD_ROW)->applyFromArray([
            'fill'       => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_DARK_BLUE]],
            'font'       => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'alignment'  => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'    => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);
        $sheet->getRowDimension(self::HEAD_ROW)->setRowHeight(36);
    }

    private function writeDataRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $rowNum = 0;

        foreach ($this->lines as $line) {
            $rowNum++;
            $row = self::DATA_START_ROW + $rowNum - 1;
            $best = $line['platform_best'] ?? null;
            $status = $line['status'] ?? 'both';

            $bestSupplier = is_array($best) ? ($best['supplier'] ?? '-') : '-';
            $bestPrice    = is_array($best) ? ($best['price'] ?? null) : null;
            $bestDiscount = is_array($best) ? ($best['discount'] ?? null) : null;

            $sheet->setCellValue("A{$row}", $rowNum);
            $sheet->setCellValue("B{$row}", $this->safeUtf8($line['query'] ?? '-'));
            $sheet->setCellValue("C{$row}", $this->safeUtf8($line['matched_product'] ?? '-'));
            $sheet->setCellValue("D{$row}", $line['similarity'] ?? 0);
            $sheet->setCellValue("E{$row}", $line['price'] ?? null);
            $sheet->setCellValue("F{$row}", $this->toPercentValue($line['discount']));
            $sheet->setCellValue("G{$row}", $this->safeUtf8($bestSupplier));
            $sheet->setCellValue("H{$row}", $bestPrice);
            $sheet->setCellValue("I{$row}", $this->toPercentValue($bestDiscount));
            $sheet->setCellValue("J{$row}", $this->diff($line['price'] ?? null, $bestPrice));
            $sheet->setCellValue("K{$row}", $this->diffPercent($line['discount'] ?? null, $bestDiscount));
            $sheet->setCellValue("L{$row}", self::STATUS_LABELS[$status] ?? $this->safeUtf8((string) $status));
        }
    }

    private function applyAutoFilter(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $lastCol): void
    {
        $sheet->setAutoFilter("A" . self::HEAD_ROW . ":{$lastCol}" . self::HEAD_ROW);
    }

    private function applyColumnWidths(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $widths = [
            'A' => 10,
            'B' => 38,
            'C' => 38,
            'D' => 14,
            'E' => 14,
            'F' => 12,
            'G' => 28,
            'H' => 14,
            'I' => 12,
            'J' => 14,
            'K' => 12,
            'L' => 18,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lastRow = $sheet->getHighestRow();
        if ($lastRow < self::DATA_START_ROW) {
            return;
        }

        $lastCol = Coordinate::stringFromColumnIndex(self::COL_COUNT);
        $dataRange = "A" . self::DATA_START_ROW . ":{$lastCol}{$lastRow}";

        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
            'font'    => ['name' => 'Arial', 'size' => 10],
        ]);

        $sheet->getStyle("A" . self::DATA_START_ROW . ":{$lastCol}{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        for ($row = self::DATA_START_ROW; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->applyFromArray([
                    'fillType'  => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_ROW_ALT],
                ]);
            }

            foreach ([5, 8, 10] as $c) {
                $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }

            foreach ([6, 9, 11] as $c) {
                $sheet->getStyleByColumnAndRow($c, $row)->getNumberFormat()->setFormatCode('0.0%');
            }

            $sheet->getStyleByColumnAndRow(4, $row)->getNumberFormat()->setFormatCode('0.0');
        }
    }

    private function toPercentValue($value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value / 100;
    }

    private function diffPercent($a, $b): ?float
    {
        if ($a === null || $a === '' || $b === null || $b === '' || ! is_numeric($a) || ! is_numeric($b)) {
            return null;
        }

        return round(((float) $a - (float) $b) / 100, 4);
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
            // Fall through to iconv IGNORE on any mbstring Error/ValueError
        }

        $iconvCleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $str);

        return $iconvCleaned !== false ? $iconvCleaned : '';
    }

    private function diff($a, $b): ?float
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return null;
        }

        return round((float) $a - (float) $b, 2);
    }
}
