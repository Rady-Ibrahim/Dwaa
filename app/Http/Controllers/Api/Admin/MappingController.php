<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnmatchedProduct;
use App\Services\MappingService;
use Illuminate\Http\Request;

class MappingController extends Controller
{
    public function __construct(private MappingService $mappingService) {}

    public function index(Request $request)
    {
        return UnmatchedProduct::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->upload_id, fn ($q, $id) => $q->where('upload_id', $id))
            ->when($request->q, fn ($q, $search) => $q->where('raw_name', 'LIKE', '%'.$search.'%'))
            ->with(['upload.supplier', 'resolvedProduct'])
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function productSearch(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        return response()->json(
            $this->mappingService->searchProducts($request->q)
        );
    }

    public function link(Request $request, UnmatchedProduct $unmatched_product)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $item = $this->mappingService->linkToExisting($unmatched_product, (int) $data['product_id']);

        return response()->json([
            'message' => 'تم الربط بنجاح',
            'item' => $item,
        ]);
    }

    public function create(Request $request, UnmatchedProduct $unmatched_product)
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
        ]);

        $product = $this->mappingService->createFromUnmatched($unmatched_product, $data);

        return response()->json([
            'message' => 'تم إنشاء المنتج والربط بنجاح',
            'product' => $product,
        ]);
    }

    public function ignore(UnmatchedProduct $unmatched_product)
    {
        $this->mappingService->ignore($unmatched_product);

        return response()->json(['message' => 'تم التجاهل']);
    }

    public function bulkLink(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['exists:unmatched_products,id'],
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $count = $this->mappingService->bulkLink($data['ids'], (int) $data['product_id']);

        return response()->json([
            'message' => 'تم ربط '.$count.' اسم بنجاح',
            'resolved' => $count,
        ]);
    }

    public function bulkIgnore(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['exists:unmatched_products,id'],
        ]);

        $count = $this->mappingService->bulkIgnore($data['ids']);

        return response()->json(['message' => 'تم تجاهل '.$count.' عنصر']);
    }
}
