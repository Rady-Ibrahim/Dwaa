<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\Request;

class SuppliersTodayController extends Controller
{
    /**
     * الموردون الذين رفعوا/حدّثوا ملفات اليوم (uploads مكتملة updated_at = اليوم).
     */
    public function index(Request $request)
    {
        $uploads = Upload::query()
            ->with('supplier:id,name,area,phone1,phone2')
            ->where('status', 'done')
            ->whereDate('updated_at', today())
            ->orderByDesc('updated_at')
            ->get();

        $bySupplier = $uploads->groupBy('supplier_id');

        $suppliers = $bySupplier
            ->map(function ($items) {
                $supplier = $items->first()->supplier;

                return [
                    'id' => $supplier?->id,
                    'name' => $supplier?->name,
                    'area' => $supplier?->area,
                    'phone' => $supplier?->phone1 ?: $supplier?->phone2,
                    'uploads_today' => $items->count(),
                    'last_upload_at' => $items->max('updated_at'),
                ];
            })
            ->filter(fn ($row) => $row['id'] !== null)
            ->values();

        return response()->json([
            'count' => $suppliers->count(),
            'suppliers' => $suppliers->all(),
        ]);
    }
}
