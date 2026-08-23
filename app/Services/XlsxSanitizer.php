<?php

namespace App\Services;

use ZipArchive;

/**
 * Prepares xlsx files produced by apps that write document metadata dates
 * (docProps/core.xml) with Arabic-Indic digits, which crashes PhpSpreadsheet
 * while loading the file ("Failed to parse time string").
 */
class XlsxSanitizer
{
    private const CORE_FILE = 'docProps/core.xml';

    private const EASTERN_ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const EXTENDED_ARABIC_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /**
     * Returns a safe path to feed readers: the original path when the archive
     * is clean, otherwise a sanitized temporary copy next to it.
     */
    public function prepareForReading(string $absolutePath): string
    {
        if (! is_file($absolutePath)) {
            return $absolutePath;
        }

        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            // Not a zip-based format (xls/csv), nothing to sanitize.
            return $absolutePath;
        }

        try {
            $coreXml = $zip->getFromName(self::CORE_FILE);
        } finally {
            $zip->close();
        }

        if ($coreXml === false || ! preg_match('/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $coreXml)) {
            return $absolutePath;
        }

        return $this->createSanitizedCopy($absolutePath, $coreXml);
    }

    /**
     * Removes the temporary sanitized copy created by prepareForReading().
     */
    public function cleanup(string $preparedPath): void
    {
        if (str_ends_with($preparedPath, '.sanitized.xlsx') && is_file($preparedPath)) {
            @unlink($preparedPath);
        }
    }

    private function createSanitizedCopy(string $absolutePath, string $coreXml): string
    {
        $sanitizedXml = str_replace(
            [...self::EASTERN_ARABIC_DIGITS, ...self::EXTENDED_ARABIC_DIGITS],
            [...range('0', '9'), ...range('0', '9')],
            $coreXml
        );

        $target = $absolutePath.'.sanitized.xlsx';

        if (! @copy($absolutePath, $target)) {
            throw new RuntimeException('تعذر تجهيز نسخة آمنة من الملف للمعالجة.');
        }

        $zip = new ZipArchive();

        if ($zip->open($target) !== true) {
            @unlink($target);

            throw new RuntimeException('تعذر تعديل بيانات الملف التالفة.');
        }

        try {
            $zip->addFromString(self::CORE_FILE, $sanitizedXml);
        } finally {
            $zip->close();
        }

        return $target;
    }
}
