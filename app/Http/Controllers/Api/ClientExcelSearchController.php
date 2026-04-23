<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchLog;
use App\Services\ExcelSearchService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientExcelSearchController extends Controller
{
    public const MAX_ROWS = 1000;
    public const MAX_SEARCHABLE_ROWS = 60;
    public const MAX_LOOP_SECONDS = 45.0;

    public function __construct(
        private SearchService $searchService,
        private ExcelSearchService $excelSearchService,
    ) {}

    public function __invoke(Request $request)
    {
        // يمنع إنهاء الطلب مبكراً عند ملفات Excel الكبيرة.
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'col_name' => ['nullable', 'string'],
            'header_rows' => ['nullable', 'integer', 'min:0', 'max:10'],
            'log_mode' => ['required', 'string', 'in:bulk,per_row'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $headerRows = (int) ($data['header_rows'] ?? 1);
        $limit = (int) ($data['limit'] ?? 20);
        $logMode = $data['log_mode'];
        $path = $request->file('file')->store('temp/excel-search/' . now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);
        $originalName = $request->file('file')->getClientOriginalName();

        try {
            $names = isset($data['col_name']) && $data['col_name'] !== ''
                ? $this->excelSearchService->readNameColumn(
                    $fullPath,
                    $data['col_name'],
                    $headerRows
                )
                : $this->excelSearchService->readNameColumnAuto($fullPath);
            $names = array_slice($names, 0, self::MAX_ROWS);

            if ($logMode === 'per_row') {
                return response()->json($this->runPerRow($request, $names, $limit, $originalName));
            }

            return response()->json($this->runBulkLog($request, $names, $limit, $originalName));
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @param  list<string>  $names
     * @return array<string, mixed>
     */
    private function runPerRow(Request $request, array $names, int $limit, string $originalName): array
    {
        $sessionId = (string) Str::uuid();
        $lines = [];
        $cache = [];
        $names = array_slice($names, 0, self::MAX_SEARCHABLE_ROWS);
        $startedAt = microtime(true);
        $stoppedByTimeout = false;

        foreach ($names as $query) {
            if ((microtime(true) - $startedAt) >= self::MAX_LOOP_SECONDS) {
                $stoppedByTimeout = true;
                break;
            }

            if (mb_strlen($query) < 3) {
                $lines[] = [
                    'query' => $query,
                    'skipped' => true,
                    'reason' => 'min_length',
                ];

                continue;
            }

            $cacheKey = mb_strtolower(trim($query));
            if (! isset($cache[$cacheKey])) {
                $cache[$cacheKey] = $this->searchService->search($request->user(), $query, $limit, [
                    'source' => SearchLog::SOURCE_EXCEL_ROW,
                    'bulk_session_id' => $sessionId,
                ]);
            }

            $lines[] = array_merge(
                $cache[$cacheKey],
                ['skipped' => false]
            );
        }

        return [
            'log_mode' => 'per_row',
            'bulk_session_id' => $sessionId,
            'filename' => $originalName,
            'rows_read' => count($names),
            'stopped_by_timeout' => $stoppedByTimeout,
            'lines' => $lines,
        ];
    }

    /**
     * @param  list<string>  $names
     * @return array<string, mixed>
     */
    private function runBulkLog(Request $request, array $names, int $limit, string $originalName): array
    {
        $originalRowsRead = count($names);
        $names = array_slice($names, 0, self::MAX_SEARCHABLE_ROWS);
        $lines = [];
        $cache = [];
        $startedAt = microtime(true);
        $stats = [
            'filename' => $originalName,
            'rows_read' => $originalRowsRead,
            'rows_processed' => count($names),
            'rows_truncated' => max(0, $originalRowsRead - count($names)),
            'rows_searched' => 0,
            'rows_skipped_short' => 0,
            'rows_with_results' => 0,
            'rows_with_offers' => 0,
            'stopped_by_timeout' => false,
        ];

        foreach ($names as $query) {
            if ((microtime(true) - $startedAt) >= self::MAX_LOOP_SECONDS) {
                $stats['stopped_by_timeout'] = true;
                break;
            }

            if (mb_strlen($query) < 3) {
                $stats['rows_skipped_short']++;

                continue;
            }
            $stats['rows_searched']++;
            $cacheKey = mb_strtolower(trim($query));
            if (! isset($cache[$cacheKey])) {
                $cache[$cacheKey] = $this->searchService->search($request->user(), $query, $limit, ['log' => false]);
            }
            $payload = $cache[$cacheKey];
            $lines[] = array_merge($payload, ['skipped' => false]);

            if ($payload['count'] > 0) {
                $stats['rows_with_results']++;
            }

            $lineHasOffers = collect($payload['results'] ?? [])
                ->contains(fn($r) => ($r['summary']['suppliers_count'] ?? 0) > 0);
            if ($lineHasOffers) {
                $stats['rows_with_offers']++;
            }
        }

        $queryLabel = mb_strlen($originalName) > 200 ? mb_substr($originalName, 0, 197) . '…' : $originalName;

        SearchLog::query()->create([
            'user_id' => $request->user()->id,
            'source' => SearchLog::SOURCE_EXCEL_BULK,
            'bulk_session_id' => null,
            'query' => $queryLabel,
            'product_id' => null,
            'results_count' => $stats['rows_with_results'],
            'had_offers' => $stats['rows_with_offers'] > 0,
            'meta' => $stats,
        ]);

        return [
            'log_mode' => 'bulk',
            'filename' => $originalName,
            'rows_read' => $stats['rows_read'],
            'summary' => $stats,
            'lines' => $lines,
        ];
    }
}
