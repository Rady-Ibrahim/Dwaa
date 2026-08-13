<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $nameAr = fake()->unique()->word().' '.fake()->numerify('####');

        return [
            'name_ar' => $nameAr,
            'name_en' => fake()->unique()->word(),
            'code' => 'P'.Str::upper(Str::random(8)),
            'normalized_name' => mb_strtolower(trim($nameAr), 'UTF-8'),
            'supplier_id' => null,
            'phonetic_key' => null,
        ];
    }
}
