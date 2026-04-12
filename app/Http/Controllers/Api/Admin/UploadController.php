<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private UploadService $uploadService) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'col_name' => ['required', 'string'],
            'col_price' => ['required', 'string'],
            'col_discount' => ['nullable', 'string'],
            'col_bonus' => ['nullable', 'string'],
        ]);

        $columnMap = [
            'name' => $data['col_name'],
            'price' => $data['col_price'],
            'discount' => $data['col_discount'] ?? null,
            'bonus' => $data['col_bonus'] ?? null,
        ];

        $upload = $this->uploadService->storeUpload(
            (int) $data['supplier_id'],
            $request->file('file'),
            $columnMap
        );

        return response()->json([
            'message' => 'جاري المعالجة في الخلفية',
            'upload_id' => $upload->id,
            'status' => $upload->status,
        ], 202);
    }

    public function show(Upload $upload)
    {
        return response()->json([
            'id' => $upload->id,
            'status' => $upload->status,
            'total_rows' => $upload->total_rows,
            'matched_count' => $upload->matched_count,
            'unmatched_count' => $upload->unmatched_count,
            'created_at' => $upload->created_at,
            'finished_at' => $upload->finished_at,
            'error_msg' => $upload->error_msg,
        ]);
    }
}
