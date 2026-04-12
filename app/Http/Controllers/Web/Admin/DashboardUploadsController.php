<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use Illuminate\Http\Request;

class DashboardUploadsController extends Controller
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

        return redirect()
            ->route('dashboard.uploads')
            ->with('status', 'تم استلام الملف وجاري المعالجة. رقم الرفع: #'.$upload->id);
    }
}
