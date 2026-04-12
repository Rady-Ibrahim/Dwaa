<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAlias;
use Illuminate\Support\Str;

/**
 * منتج الشيت مربوط بمورد واحد: نفس الاسم من موردين = صفّان في products + عرضان منفصلان في البحث.
 */
class SupplierOfferProductResolver
{
    public function resolve(string $normalized, string $rawName, int $supplierId): Product
    {
        $product = Product::query()->firstOrCreate(
            [
                'supplier_id' => $supplierId,
                'normalized_name' => $normalized,
            ],
            [
                'name_ar' => Str::limit($rawName, 255),
                'name_en' => null,
                'code' => $this->uniqueAutoCode(),
            ]
        );

        ProductAlias::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'normalized_name' => $normalized,
            ],
            [
                'name' => Str::limit($rawName, 255),
            ]
        );

        return $product;
    }

    private function uniqueAutoCode(): string
    {
        do {
            $code = 'AUTO-'.strtoupper(bin2hex(random_bytes(5)));
        } while (Product::query()->where('code', $code)->exists());

        return $code;
    }
}
