<?php

namespace Database\Factories;

use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchLog>
 */
class SearchLogFactory extends Factory
{
    protected $model = SearchLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source' => SearchLog::SOURCE_TEXT,
            'bulk_session_id' => null,
            'query' => fake()->word(),
            'product_id' => null,
            'results_count' => fake()->numberBetween(0, 20),
            'had_offers' => fake()->boolean(),
            'meta' => null,
        ];
    }
}
