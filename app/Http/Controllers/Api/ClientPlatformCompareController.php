<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcelSearchService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientPlatformCompareController extends Controller
{
    private const MAX_ROWS = 120;

    public function __construct(
        private SearchService $searchService,
        private ExcelSearchService $excelSearchService,
    ) {}

    public function __invoke(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $path = $request->file('file')->store('temp/compare-platform/' . now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);
        $limit = (int) ($request->integer('limit') ?: 3);

        try {
            $rows = $this->excelSearchService->readRowsAutoForPlatformCompare($fullPath, self::MAX_ROWS);
            $lines = [];

            foreach ($rows as $row) {
                $query = (string) $row['name'];
                if (mb_strlen(trim($query)) < 3) {
                    $lines[] = [
                        'query' => $query,
                        'sheet' => $row,
                        'skipped' => true,
                        'reason' => 'min_length',
                    ];
                    continue;
                }

                // Use pharmacy search for better medication matching
                $results = $this->searchService->pharmacySearch($query, $limit);

                // Format results (copy from formatProduct logic)
                $formattedResults = $results->map(fn($product) => $this->searchService->formatProduct($product));
                $first = $formattedResults->first();
                $best = $first['offers'][0] ?? null;
                $sheetPrice = $row['price'];
                $sheetDiscount = $row['discount'];
                $platformPrice = isset($best['price']) ? (float) $best['price'] : null;
                $platformDiscount = isset($best['discount']) ? (float) $best['discount'] : null;

                // Debug: Include what was searched and result count
                $lines[] = [
                    'query' => $query,
                    'sheet' => $row,
                    'search_results_count' => $results->count(),
                    'matched_product' => $first['name_ar'] ?? $first['name_en'] ?? null,
                    'platform_best' => [
                        'supplier' => $best['supplier'] ?? null,
                        'area' => $best['area'] ?? null,
                        'phone' => $best['supplier_phone'] ?? null,
                        'price' => $platformPrice,
                        'discount' => $platformDiscount,
                    ],
                    'comparison' => [
                        'price_diff' => ($sheetPrice !== null && $platformPrice !== null) ? round($sheetPrice - $platformPrice, 2) : null,
                        'discount_diff' => ($sheetDiscount !== null && $platformDiscount !== null) ? round($sheetDiscount - $platformDiscount, 2) : null,
                    ],
                    'count' => (int) ($results->count()),
                    'skipped' => false,
                ];
            }

            usort($lines, function (array $a, array $b): int {
                $aMatch = (int) (($a['count'] ?? 0) > 0);
                $bMatch = (int) (($b['count'] ?? 0) > 0);
                if ($aMatch !== $bMatch) {
                    return $bMatch <=> $aMatch;
                }
                $aOffer = (int) (! empty($a['platform_best']['supplier']));
                $bOffer = (int) (! empty($b['platform_best']['supplier']));
                if ($aOffer !== $bOffer) {
                    return $bOffer <=> $aOffer;
                }
                return strcmp((string) ($a['query'] ?? ''), (string) ($b['query'] ?? ''));
            });

            return response()->json([
                'rows_read' => count($rows),
                'lines' => $lines,
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }
}
