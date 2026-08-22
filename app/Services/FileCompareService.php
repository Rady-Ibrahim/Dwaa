<?php

namespace App\Services;

use App\Concerns\HasExcelHeaderAliases;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class FileCompareService
{
    use HasExcelHeaderAliases;

    private const MAX_PRICE_DIFF = 2.0;

    private const DRUG_STOP_WORDS = [
        'اقراص', 'قرص', 'كبسول', 'كبسوله', 'كبسولات', 'حبوب', 'شراب', 'حقن', 'قطرات', 'قطره',
        'نقط', 'مرهم', 'كريم', 'جل', 'بخاخ', 'سبراي', 'محلول', 'معلق', 'لبوس', 'تحاميل',
        'امبول', 'امبولات', 'شريط', 'شرايط', 'باكت', 'علبه', 'زجاجه', 'مسحوق', 'بودره', 'سيروم',
        'لوشن', 'لوسيون', 'مجم', 'ملجم', 'مليجرام', 'جرام', 'ملى', 'مللى', 'مل', 'سم', 'لتر', 'كجم',
        'فموي', 'وريدي', 'موضعي', 'مستمر', 'معجل', 'مؤخر', 'سعر', 'جنيه', 'جنية',
        'مع', 'وزن', 'عبوه', 'محيط', 'العين', 'مطهر', 'غسول', 'جديد', 'احمر', 'اكسترا', 'ال',
        'جيل', 'كبير', 'صغير', 'كبيره', 'صغيره',
        'صن', 'بلوك', 'بديل', 'سيرم', 'شامبو', 'مضاد', 'حيوي', 'ادويه', 'علاج', 'دواء',
        'مستحضر', 'كبسولات',
    ];

    private static ?array $stopWordMap = null;

    public function __construct(
        private NormalizerService $normalizer,
        private UploadService $uploadService,
    ) {}

    /**
     * @param  array{name:string,price:string,discount?:string}  $mapA  column letters or indices
     * @param  array{name:string,price:string,discount?:string}  $mapB
     * @return array{pairs: list<array<string,mixed>>, unmatched_a: list<array<string,mixed>>, unmatched_b: list<array<string,mixed>>}
     */
    public function compareUploadedFiles(
        string $storagePathA,
        string $storagePathB,
        array $mapA,
        array $mapB,
        float $minSimilarityPercent = 80.0,
    ): array {
        $pathA = Storage::disk('local')->path($storagePathA);
        $pathB = Storage::disk('local')->path($storagePathB);

        $normA = $this->hasManualColumnMap($mapA) ? $this->uploadService->normalizeColumnMap($mapA) : null;
        $normB = $this->hasManualColumnMap($mapB) ? $this->uploadService->normalizeColumnMap($mapB) : null;

        $rowsA = $this->extractRows($pathA, $normA);
        $rowsB = $this->extractRows($pathB, $normB);

        $indexB = $this->buildRowIndexByNormName($rowsB);

        $usedB = [];
        $pairs = [];
        $unmatchedA = [];

        foreach ($rowsA as $a) {
            $nA = $a['norm_name'];
            $bestJ = null;
            $bestScore = 0.0;

            if (isset($indexB[$nA])) {
                foreach ($indexB[$nA] as $j) {
                    if (isset($usedB[$j])) {
                        continue;
                    }
                    $bestJ = $j;
                    $bestScore = 100.0;
                    break;
                }
            }

            if ($bestJ === null) {
                $tokensA = $this->contentTokens($nA);
                $numbersA = $this->contentNumbers($nA);

                if (! empty($tokensA)) {
                    $candidateKeys = array_unique(array_merge(
                        [$tokensA[0]],
                        array_slice($tokensA, 1),
                    ));

                    $seenCandidates = [];
                    foreach ($candidateKeys as $token) {
                        if (isset($indexB[$token])) {
                            foreach ($indexB[$token] as $j) {
                                if (isset($usedB[$j], $seenCandidates[$j])) {
                                    continue;
                                }
                                $seenCandidates[$j] = true;

                                $b = $rowsB[$j];
                                $score = $this->drugOverlapScorePrepared(
                                    $tokensA,
                                    $this->contentTokens($b['norm_name']),
                                    $numbersA,
                                    $this->contentNumbers($b['norm_name']),
                                );

                                if ($score >= $minSimilarityPercent && $score > $bestScore) {
                                    $bestScore = $score;
                                    $bestJ = $j;
                                }
                            }
                        }
                    }
                }
            }

            if ($bestJ !== null) {
                $b = $rowsB[$bestJ];

                if (! $this->hasAcceptablePriceDiff($a['price'], $b['price'])) {
                    $unmatchedA[] = $this->stripRow($a);

                    continue;
                }

                $usedB[$bestJ] = true;
                $pairs[] = [
                    'file_a' => $this->stripRow($a),
                    'file_b' => $this->stripRow($b),
                    'similarity_percent' => round($bestScore, 1),
                ];
            } else {
                $unmatchedA[] = $this->stripRow($a);
            }
        }

        $unmatchedB = [];
        foreach ($rowsB as $j => $b) {
            if (! isset($usedB[$j])) {
                $unmatchedB[] = $this->stripRow($b);
            }
        }

        return [
            'pairs' => $pairs,
            'unmatched_a' => $unmatchedA,
            'unmatched_b' => $unmatchedB,
        ];
    }

    /**
     * يقبل الزوج فقط إذا كان فرق السعر بين الملفين ≤ 2 (فرق مطلق).
     */
    private function hasAcceptablePriceDiff(float $priceA, float $priceB): bool
    {
        if ($priceA <= 0 || $priceB <= 0) {
            return true;
        }

        return abs($priceA - $priceB) <= self::MAX_PRICE_DIFF;
    }

    /**
     * @param  array{raw_name:string,norm_name:string,price:float,discount:float,bonus?:string}  $row
     * @return array{raw_name:string,price:float,discount:float,bonus?:string|null}
     */
    private function stripRow(array $row): array
    {
        return [
            'raw_name' => $row['raw_name'],
            'price' => $row['price'],
            'discount' => $row['discount'],
            'bonus' => $row['bonus'] ?? null,
        ];
    }

    /**
     * @param  list<array{raw_name:string,norm_name:string,price:float,discount:float,bonus?:string}>  $rows
     * @return array<string, list<int>>
     */
    private function buildRowIndexByNormName(array $rows): array
    {
        $index = [];
        foreach ($rows as $j => $b) {
            $key = $b['norm_name'];
            $index[$key][] = $j;

            foreach ($this->contentTokens($key) as $token) {
                if (mb_strlen($token) >= 3 && ! isset(self::stopWordMap()[$token])) {
                    $index[$token][] = $j;
                }
            }
        }

        return $index;
    }

    // ─── Text Matching ───────────────────────────────────────────────────

    private function drugOverlapScorePrepared(
        array $tokensA,
        array $tokensB,
        array $numbersA,
        array $numbersB,
    ): float {
        if (! empty($numbersA) && ! empty($numbersB)) {
            if (array_intersect($numbersA, $numbersB) === []) {
                return 0.0;
            }
            if (($numbersA[0] ?? null) !== ($numbersB[0] ?? null)) {
                return 0.0;
            }
        }

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        if (($tokensA[0] ?? '') !== ($tokensB[0] ?? '')) {
            return 0.0;
        }

        if (isset($tokensA[1], $tokensB[1]) && $tokensA[1] !== $tokensB[1]) {
            return 0.0;
        }

        $shared = [];
        foreach ($tokensA as $tokenA) {
            if (in_array($tokenA, $tokensB, true)) {
                $shared[] = $tokenA;
            }
        }

        if (empty($shared)) {
            return 0.0;
        }

        $longestShared = max(array_map('mb_strlen', $shared));
        if ($longestShared < 4) {
            return 0.0;
        }

        return round((count($shared) / max(count($tokensA), count($tokensB))) * 100, 1);
    }

    private function contentTokens(string $name): array
    {
        preg_match_all('/\p{L}+/u', $name, $matches);

        $out = [];
        foreach ($matches[0] as $run) {
            $lower = mb_strtolower($run);
            $lower = str_replace(
                ['أ', 'إ', 'آ', 'ٱ', 'ى', 'ئ', 'ة', 'ؤ', 'ء'],
                ['ا', 'ا', 'ا', 'ا', 'ي', 'ي', 'ه', 'و', ''],
                $lower
            );

            if (mb_strlen($lower) < 2) {
                continue;
            }

            if (isset(self::stopWordMap()[$lower])) {
                continue;
            }

            $out[] = $lower;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function contentNumbers(string $name): array
    {
        $text = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $name
        );

        preg_match_all('/\d+(?:\.\d+)?/u', $text, $matches);

        return $matches[0];
    }

    private static function stopWordMap(): array
    {
        return self::$stopWordMap ??= array_flip(self::DRUG_STOP_WORDS);
    }

    // ─── Excel Parsing ───────────────────────────────────────────────────

    /**
     * @param  array{name:int,price:int,discount?:int,bonus?:int}|null  $columnIndexes
     * @return list<array{raw_name:string,norm_name:string,price:float,discount:float,bonus?:string}>
     */
    private function extractRows(string $absolutePath, ?array $columnIndexes): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $out = [];
        $detectedHeader = $columnIndexes !== null;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! $detectedHeader) {
                $columnIndexes = $this->detectColumnMapFromHeaderRow($row);
                if ($columnIndexes === null) {
                    continue;
                }
                $detectedHeader = true;

                continue;
            }

            $nameIdx = $columnIndexes['name'];
            $priceIdx = $columnIndexes['price'];
            $rawName = trim((string) ($row[$nameIdx] ?? ''));
            $price = (float) ($row[$priceIdx] ?? 0);
            $discount = isset($columnIndexes['discount'])
                ? (float) ($row[$columnIndexes['discount']] ?? 0)
                : 0.0;
            $bonus = isset($columnIndexes['bonus'])
                ? trim((string) ($row[$columnIndexes['bonus']] ?? ''))
                : '';

            if ($rawName === '' || $price <= 0) {
                continue;
            }

            $out[] = [
                'raw_name' => $rawName,
                'norm_name' => $this->normalizer->normalize($rawName),
                'price' => $price,
                'discount' => $discount,
                'bonus' => $bonus !== '' ? $bonus : null,
            ];
        }

        if (! $detectedHeader) {
            throw new RuntimeException('تعذر اكتشاف أعمدة الاسم والسعر من هيدر ملف المقارنة.');
        }

        return $out;
    }

    /**
     * @param  array{name?:mixed,price?:mixed,discount?:mixed}  $columnMap
     */
    private function hasManualColumnMap(array $columnMap): bool
    {
        return isset($columnMap['name'], $columnMap['price'])
            && $columnMap['name'] !== null
            && $columnMap['price'] !== null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array{name:int,price:int,discount?:int}|null
     */
    private function detectColumnMapFromHeaderRow(array $row): ?array
    {
        $aliases = [
            'name' => self::NAME_HEADER_ALIASES,
            'price' => self::PRICE_HEADER_ALIASES,
            'discount' => self::DISCOUNT_HEADER_ALIASES,
            'bonus' => self::BONUS_HEADER_ALIASES,
        ];
        $map = [];

        foreach ($row as $idx => $value) {
            $header = $this->normalizeHeader((string) $value);
            if ($header === '') {
                continue;
            }

            foreach ($aliases as $key => $keyAliases) {
                if (isset($map[$key])) {
                    continue;
                }

                $isName = ($key === 'name');
                if ($this->headerMatchesAliases($header, $keyAliases, $isName)) {
                    $map[$key] = (int) $idx;
                    break;
                }
            }
        }

        if (! isset($map['name'], $map['price'])) {
            return null;
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $aliases
     */
    private function headerMatchesAliases(string $normalizedHeader, array $aliases, bool $isName = false): bool
    {
        $isLikelyCodeColumn = $this->looksLikeCodeHeader($normalizedHeader);

        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeHeader($alias);
            if ($normalizedAlias === '') {
                continue;
            }

            if ($isName && $isLikelyCodeColumn && in_array($normalizedAlias, ['الصنف', 'item', 'product'], true)) {
                continue;
            }

            if ($normalizedHeader === $normalizedAlias) {
                return true;
            }

            if (mb_strlen($normalizedAlias) >= 4 && str_contains($normalizedHeader, $normalizedAlias)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeCodeHeader(string $normalizedHeader): bool
    {
        return str_contains($normalizedHeader, 'رقم')
            || str_contains($normalizedHeader, 'كود')
            || str_contains($normalizedHeader, 'code')
            || str_contains($normalizedHeader, 'id')
            || str_contains($normalizedHeader, 'sku');
    }

    private function normalizeHeader(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $normalized);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
