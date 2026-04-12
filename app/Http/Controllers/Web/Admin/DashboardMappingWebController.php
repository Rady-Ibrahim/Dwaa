<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnmatchedProduct;
use App\Services\MappingService;
use Illuminate\Http\Request;

class DashboardMappingWebController extends Controller
{
    public function __construct(private MappingService $mappingService) {}

    public function searchProducts(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'min:2']]);

        return response()->json(
            $this->mappingService->searchProducts($request->q, 15)->values()
        );
    }

    public function link(Request $request, UnmatchedProduct $unmatched_product)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $this->mappingService->linkToExisting($unmatched_product, (int) $data['product_id']);

        return back()->with('status', 'تم الربط وأضيف alias للمستقبل.');
    }

    public function createProduct(Request $request, UnmatchedProduct $unmatched_product)
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
        ]);

        $this->mappingService->createFromUnmatched($unmatched_product, $data);

        return back()->with('status', 'تم إنشاء المنتج والربط.');
    }

    public function ignore(UnmatchedProduct $unmatched_product)
    {
        $this->mappingService->ignore($unmatched_product);

        return back()->with('status', 'تم التجاهل.');
    }
}
