<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\UserFavorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = UserFavorite::query()
            ->where('user_id', $request->user()->id)
            ->with(['product' => function ($q) {
                $q->select(['id', 'name_ar', 'name_en', 'code']);
            }])
            ->latest()
            ->paginate(30);

        return response()->json($favorites);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        UserFavorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $data['product_id'],
        ]);

        return response()->json(['message' => 'تمت الإضافة للمفضلة'], 201);
    }

    public function destroy(Request $request, Product $product)
    {
        UserFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(['message' => 'تم الحذف من المفضلة']);
    }
}
