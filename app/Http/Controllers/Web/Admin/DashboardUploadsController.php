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
        ]);

        $columnMap = [
            'name' => 'C',
            'price' => 'B',
            'discount' => 'A',
            'bonus' => null,
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
