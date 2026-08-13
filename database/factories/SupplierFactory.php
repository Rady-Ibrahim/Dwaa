<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'phone1' => fake()->numerify('01#########'),
            'phone2' => null,
            'area' => fake()->city(),
            'is_active' => true,
        ];
    }
}
