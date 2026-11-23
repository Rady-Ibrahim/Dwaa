<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_product_and_logs_cache_hits(): void
    {
        Cache::flush();

        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'supplier_id' => $supplier->id,
            'name_ar' => 'باراسيتامول 500',
            'name_en' => null,
            'normalized_name' => 'باراسيتامول 500',
        ]);

        Offer::query()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'price' => 15,
            'discount' => 10,
            'expires_at' => now()->addDays(3),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
        ]);

        Sanctum::actingAs($user);

        $first = $this->getJson('/api/search?q=باراسيتامول');
        $first->assertOk();
        $first->assertJsonPath('count', 1);
        $first->assertJsonPath('results.0.id', $product->id);

        $this->getJson('/api/search?q=باراسيتامول')->assertOk();

        $this->assertSame(2, SearchLog::query()
            ->where('user_id', $user->id)
            ->where('source', SearchLog::SOURCE_TEXT)
            ->count());
    }
}
