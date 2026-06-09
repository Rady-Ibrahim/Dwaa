<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ClientProductsController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_discount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $offersQuery = Offer::query()
            ->active()
            ->with([
                'product:id,name_ar,name_en,code',
                'supplier:id,name,area,phone1,phone2',
            ])
            ->orderByDesc('id');

        if (! empty($data['supplier_id'])) {
            $offersQuery->where('supplier_id', (int) $data['supplier_id']);
        }

        if (isset($data['min_price'])) {
            $offersQuery->where('price', '>=', (float) $data['min_price']);
        }
        if (isset($data['max_price'])) {
            $offersQuery->where('price', '<=', (float) $data['max_price']);
        }
        if (isset($data['min_discount'])) {
            $offersQuery->where('discount', '>=', (float) $data['min_discount']);
        }
        if (isset($data['max_discount'])) {
            $offersQuery->where('discount', '<=', (float) $data['max_discount']);
        }

        $perPage = 30;
        $paginator = $offersQuery->paginate($perPage);

        $suppliers = Supplier::query()
            ->whereIn('id', Offer::query()->active()->select('supplier_id')->distinct()->pluck('supplier_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $offers = $paginator->getCollection()->map(function (Offer $offer) {
            $supplierPhone = $offer->supplier?->phone1 ?: $offer->supplier?->phone2;

            return [
                'offer_id' => $offer->id,
                'product_id' => $offer->product_id,
                'product_name' => $offer->product?->name_ar ?: $offer->product?->name_en,
                'product_code' => $offer->product?->code,
                'supplier' => $offer->supplier?->name,
                'supplier_phone' => $supplierPhone,
                'area' => $offer->supplier?->area,
                'price' => (float) $offer->price,
                'discount' => (float) $offer->discount,
                'bonus' => $offer->bonus,
                'expires_at' => $offer->expires_at?->toDateString(),
            ];
        })->values()->all();

        return response()->json([
            'suppliers'      => $suppliers,
            'data'           => $offers,
            'current_page'   => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'total'          => $paginator->total(),
            'prev_page_url'  => $paginator->previousPageUrl(),
            'next_page_url'  => $paginator->nextPageUrl(),
        ]);
    }
}

