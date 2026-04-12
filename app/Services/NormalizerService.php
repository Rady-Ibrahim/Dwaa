<?php

namespace App\Services;

class NormalizerService
{
    public function normalize(string $text): string
    {
        $text = $this->trimAndCollapse($text);
        $text = $this->toLowercase($text);
        $text = $this->unifyArabicLetters($text);
        $text = $this->removeDiacritics($text);
        $text = $this->removeSymbols($text);
        $text = $this->unifyMedicalAbbreviations($text);
        $text = $this->finalCollapse($text);

        return $text;
    }

    private function trimAndCollapse(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    private function toLowercase(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }

    private function unifyArabicLetters(string $text): string
    {
        $replacements = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
            'ء' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    private function removeDiacritics(string $text): string
    {
        return preg_replace('/[\x{064B}-\x{065F}]/u', '', $text) ?? $text;
    }

    private function removeSymbols(string $text): string
    {
        $text = preg_replace('/[^\p{Arabic}\p{Latin}\d\s]/u', ' ', $text) ?? $text;

        return $text;
    }

    private function unifyMedicalAbbreviations(string $text): string
    {
        $abbreviations = [
            '/\b(\d+)\s*milligram[s]?\b/i' => '$1mg',
            '/\b(\d+)\s*microgram[s]?\b/i' => '$1mcg',
            '/\b(\d+)\s*gram[s]?\b/i' => '$1g',
            '/\b(\d+)\s*ml\b/i' => '$1ml',
            '/\b(\d+)\s*iu\b/i' => '$1iu',
            '/\btablets?\b/i' => 'tab',
            '/\tcapsules?\b/i' => 'cap',
            '/\bsyrup\b/i' => 'syr',
            '/\binjection\b/i' => 'inj',
            '/\bsuppository\b/i' => 'supp',
            '/\bcream\b/i' => 'cr',
            '/\bointment\b/i' => 'oint',
            '/\bsolution\b/i' => 'sol',
            '/\bsuspension\b/i' => 'susp',
            '/\bأقراص\b/u' => 'tab',
            '/\bكبسول\b/u' => 'cap',
            '/\bشراب\b/u' => 'syr',
            '/\bحقن\b/u' => 'inj',
            '/\bمرهم\b/u' => 'oint',
            '/\bقطرة\b/u' => 'drops',
            '/\bقطر\b/u' => 'drops',
        ];

        return preg_replace(array_keys($abbreviations), array_values($abbreviations), $text) ?? $text;
    }

    private function finalCollapse(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
