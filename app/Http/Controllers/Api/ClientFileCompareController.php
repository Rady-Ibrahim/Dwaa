<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComparisonLog;
use App\Services\FileCompareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientFileCompareController extends Controller
{
    public function __construct(private FileCompareService $fileCompareService) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'file_a' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'file_b' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'col_name_a' => ['required', 'string'],
            'col_price_a' => ['required', 'string'],
            'col_discount_a' => ['nullable', 'string'],
            'col_name_b' => ['required', 'string'],
            'col_price_b' => ['required', 'string'],
            'col_discount_b' => ['nullable', 'string'],
            'min_similarity' => ['nullable', 'numeric', 'min:50', 'max:100'],
        ]);

        $pathA = $request->file('file_a')->store('temp/compare/'.now()->format('Y/m'), 'local');
        $pathB = $request->file('file_b')->store('temp/compare/'.now()->format('Y/m'), 'local');

        try {
            $result = $this->fileCompareService->compareUploadedFiles(
                $pathA,
                $pathB,
                [
                    'name' => $data['col_name_a'],
                    'price' => $data['col_price_a'],
                    'discount' => $data['col_discount_a'] ?? null,
                ],
                [
                    'name' => $data['col_name_b'],
                    'price' => $data['col_price_b'],
                    'discount' => $data['col_discount_b'] ?? null,
                ],
                (float) ($data['min_similarity'] ?? 80)
            );

            ComparisonLog::query()->create([
                'user_id' => $request->user()->id,
                'pairs_count' => count($result['pairs']),
                'unmatched_a_count' => count($result['unmatched_a']),
                'unmatched_b_count' => count($result['unmatched_b']),
                'meta' => [
                    'min_similarity' => (float) ($data['min_similarity'] ?? 80),
                ],
            ]);

            return response()->json($result);
        } finally {
            Storage::disk('local')->delete([$pathA, $pathB]);
        }
    }
}
