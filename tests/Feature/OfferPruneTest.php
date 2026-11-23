<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OfferPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deactivates_expired_offers_in_chunks_by_default(): void
    {
        [$expired, $active] = $this->createOffers();

        Artisan::call('offers:prune-expired', ['--chunk' => 100]);

        $this->assertFalse($expired->refresh()->is_active);
        $this->assertTrue($active->refresh()->is_active);
        $this->assertDatabaseHas('offers', ['id' => $expired->id]);
    }

    public function test_prune_can_physically_delete_expired_offers(): void
    {
        [$expired, $active] = $this->createOffers();

        Artisan::call('offers:prune-expired', ['--chunk' => 100, '--delete' => true]);

        $this->assertDatabaseMissing('offers', ['id' => $expired->id]);
        $this->assertDatabaseHas('offers', ['id' => $active->id]);
    }

    /**
     * @return array{0: Offer, 1: Offer}
     */
    private function createOffers(): array
    {
        $supplier = Supplier::factory()->create();
        $productA = Product::factory()->create(['supplier_id' => $supplier->id]);
        $productB = Product::factory()->create(['supplier_id' => $supplier->id]);

        $expired = Offer::query()->create([
            'product_id' => $productA->id,
            'supplier_id' => $supplier->id,
            'price' => 10,
            'discount' => 0,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $active = Offer::query()->create([
            'product_id' => $productB->id,
            'supplier_id' => $supplier->id,
            'price' => 20,
            'discount' => 0,
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        return [$expired, $active];
    }
}
