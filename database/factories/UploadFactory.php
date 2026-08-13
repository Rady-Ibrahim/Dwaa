<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Upload>
 */
class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'file_path' => 'uploads/'.fake()->uuid().'.xlsx',
            'column_map' => ['name' => 0, 'price' => 1],
            'status' => 'done',
            'total_rows' => fake()->numberBetween(1, 1000),
            'matched_count' => fake()->numberBetween(0, 1000),
            'unmatched_count' => 0,
            'error_msg' => null,
            'started_at' => now(),
            'finished_at' => now(),
        ];
    }
}
