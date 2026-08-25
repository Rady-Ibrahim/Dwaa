<?php

namespace App\Http\Controllers\Api;

use App\Exports\SavedComparisonExport;
use App\Http\Controllers\Controller;
use App\Models\SavedComparison;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SavedComparisonController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        return SavedComparison::query()
            ->where('user_id', $request->user()->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ]);

        $row = SavedComparison::query()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? null,
            'payload' => $data['payload'],
        ]);

        return response()->json($row, 201);
    }

    public function show(Request $request, SavedComparison $savedComparison)
    {
        if ($savedComparison->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($savedComparison);
    }

    public function destroy(Request $request, SavedComparison $savedComparison)
    {
        if ($savedComparison->user_id !== $request->user()->id) {
            abort(403);
        }

        $savedComparison->delete();

        return response()->json(['message' => 'تم الحذف']);
    }

    public function export(Request $request, SavedComparison $savedComparison)
    {
        if ($savedComparison->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(600);
            }

            $payload = $savedComparison->payload ?? [];
            $type    = $payload['type'] ?? 'file_compare';
            $data    = $type === 'platform_compare' ? ($payload['lines'] ?? []) : ($payload['pairs'] ?? []);

            $export = new SavedComparisonExport($type, $data, $savedComparison->title ?? 'Med RANKO');
            $spreadsheet = $export->build();

            $safeTitle  = preg_replace('/[^\w\-]/u', '_', $savedComparison->title ?? 'export');
            $filename   = "Med_RANKO_{$safeTitle}_" . now()->format('Y-m-d_His') . '.xlsx';
            $tempPath   = storage_path('app/' . $filename);
            $writer     = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return response()->download($tempPath, $filename, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'فشل التصدير: ' . $e->getMessage()], 500);
        }
    }
}
