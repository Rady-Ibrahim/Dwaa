<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\Supplier;
use App\Models\SupplierRanking;
use App\Models\Upload;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RankingAndSuppliersTodayTest extends TestCase
{
    use RefreshDatabase;

    private function clientUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true,
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function makeActiveOffer(Supplier $supplier, Product $product, float $discount): Offer
    {
        return Offer::query()->updateOrCreate(
            ['supplier_id' => $supplier->id, 'product_id' => $product->id],
            ['price' => 100, 'discount' => $discount, 'expires_at' => now()->addDays(7)]
        );
    }

    public function test_ranking_discount_quality_index_formula(): void
    {
        $a = Supplier::factory()->create(['is_active' => true]);
        $b = Supplier::factory()->create(['is_active' => true]);
        $product = Product::factory()->create();

        $this->makeActiveOffer($a, $product, 20);
        $this->makeActiveOffer($b, $product, 10);

        app(RankingService::class)->recalculateAll();

        // أعلى خصم على الاسم = 20، لذلك المورد الأول يملكه (100%) والثاني لا يملكه (0%).
        $this->assertSame(100.0, (float) SupplierRanking::where('supplier_id', $a->id)->first()->discount_quality_index);
        $this->assertSame(0.0, (float) SupplierRanking::where('supplier_id', $b->id)->first()->discount_quality_index);
    }

    public function test_uploads_compare_matches_same_product_across_suppliers_by_normalized_name(): void
    {
        $supplierA = Supplier::factory()->create(['name' => 'مورد أ']);
        $supplierB = Supplier::factory()->create(['name' => 'مورد ب']);
        $uploadA = Upload::factory()->create(['supplier_id' => $supplierA->id]);
        $uploadB = Upload::factory()->create(['supplier_id' => $supplierB->id]);

        $offer = static function (Supplier $supplier, Upload $upload, Product $product, float $discount): Offer {
            return Offer::query()->updateOrCreate(
                ['supplier_id' => $supplier->id, 'product_id' => $product->id],
                ['price' => 100, 'discount' => $discount, 'expires_at' => now()->addDays(7), 'upload_id' => $upload->id]
            );
        };

        // نفس الدواء من موردين مختلفين = صفّان منفصلان في products لكن بنفس normalized_name
        $shared = 'دواء مشترك';
        $productA = Product::factory()->create([
            'supplier_id' => $supplierA->id,
            'normalized_name' => $shared,
            'name_ar' => $shared,
        ]);
        $productB = Product::factory()->create([
            'supplier_id' => $supplierB->id,
            'normalized_name' => $shared,
            'name_ar' => $shared,
        ]);

        $offer($supplierA, $uploadA, $productA, 25);
        $offer($supplierB, $uploadB, $productB, 30);

        // صنف خاص بالمورد الأول فقط
        $onlyA = 'دواء منفرد';
        $offer($supplierA, $uploadA, Product::factory()->create([
            'supplier_id' => $supplierA->id,
            'normalized_name' => $onlyA,
            'name_ar' => $onlyA,
        ]), 10);

        Sanctum::actingAs($this->clientUser());

        $response = $this->postJson('/api/compare-uploads', [
            'upload_id_a' => $uploadA->id,
            'upload_id_b' => $uploadB->id,
        ]);

        $response->assertOk();

        $lines = collect($response->json('lines'));
        $statuses = $lines->pluck('status')->countBy();

        $this->assertSame(1, $statuses->get('both') ?? 0);
        $this->assertSame(1, $statuses->get('only_a') ?? 0);
        $this->assertSame(0, $statuses->get('only_b') ?? 0);

        $matched = $lines->firstWhere('status', 'both');
        $this->assertSame('دواء مشترك', $matched['query']);
        $this->assertSame(100.0, (float) $matched['sheet']['price']);
        $this->assertSame(25.0, (float) $matched['sheet']['discount']);
        $this->assertSame(30.0, (float) $matched['platform_best']['discount']);
        $this->assertSame('مورد ب', $matched['platform_best']['supplier']);
    }

    public function test_ranking_endpoint_sorts_by_items(): void
    {
        $big = Supplier::factory()->create(['is_active' => true]);
        $small = Supplier::factory()->create(['is_active' => true]);

        $products = Product::factory()->count(3)->create();
        foreach ($products as $product) {
            $this->makeActiveOffer($big, $product, 5);
        }
        $this->makeActiveOffer($small, Product::factory()->create(), 5);

        app(RankingService::class)->recalculateAll();
        Sanctum::actingAs($this->clientUser());

        $this->getJson('/api/ranking?sort=items')
            ->assertOk()
            ->assertJsonPath('data.0.supplier.name', $big->name)
            ->assertJsonPath('data.0.total_items_count', 3);
    }

    public function test_ranking_endpoint_rejects_invalid_sort(): void
    {
        Sanctum::actingAs($this->clientUser());

        $this->getJson('/api/ranking?sort=invalid')->assertStatus(422);
    }

    public function test_suppliers_today_endpoint_counts_done_uploads(): void
    {
        $supplier = Supplier::factory()->create();
        $other = Supplier::factory()->create();

        Upload::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'done',
            'updated_at' => today(),
        ]);
        Upload::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'done',
            'updated_at' => today(),
        ]);
        Upload::factory()->create([
            'supplier_id' => $other->id,
            'status' => 'done',
            'updated_at' => now()->subDays(2),
        ]);

        Sanctum::actingAs($this->clientUser());

        $this->getJson('/api/suppliers/today')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('suppliers.0.id', $supplier->id)
            ->assertJsonPath('suppliers.0.uploads_today', 2);
    }

    public function test_admin_api_search_logs_endpoint(): void
    {
        $user = $this->clientUser();
        SearchLog::factory()->count(5)->create(['user_id' => $user->id, 'query' => 'باراسيتامول']);
        SearchLog::factory()->count(3)->create(['user_id' => $user->id, 'query' => 'فولتارين']);

        Sanctum::actingAs($this->adminUser());

        $this->getJson('/api/admin/analytics/users/'.$user->id.'/search-logs')
            ->assertOk()
            ->assertJsonPath('total', 8);

        $this->getJson('/api/admin/analytics/search-logs?q=فولتارين')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_admin_web_search_logs_pages(): void
    {
        $user = $this->clientUser();
        SearchLog::factory()->create(['user_id' => $user->id, 'query' => 'تجريبي']);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('dashboard.analytics.search-logs'))
            ->assertOk()
            ->assertSee('تجريبي');

        $this->actingAs($admin)
            ->get(route('dashboard.analytics.users.search-logs', $user))
            ->assertOk()
            ->assertSee('تجريبي');
    }

    public function test_admin_dashboard_index_renders_suppliers_today_widget(): void
    {
        $supplier = Supplier::factory()->create();
        Upload::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'done',
            'updated_at' => today(),
        ]);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('موردين اليوم')
            ->assertSee($supplier->name);
    }

    public function test_admin_analytics_page_renders_search_logs_link(): void
    {
        SearchLog::factory()->create(['user_id' => $this->clientUser()->id, 'query' => 'تجريبي']);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('dashboard.analytics'))
            ->assertOk()
            ->assertSee('سجل بحث العملاء');
    }

    public function test_client_ranking_page_renders(): void
    {
        $user = $this->clientUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withCookie('client_token', $token)
            ->get('/client/ranking')
            ->assertOk()
            ->assertSee('ترتيب الموردين')
            ->assertSee('themeToggle')
            ->assertSee('clientLightThemeOverrides');

        $this->withCookie('client_token', $token)
            ->get('/client/suppliers-today')
            ->assertOk()
            ->assertSee('موردين اليوم');
    }

    public function test_client_ranking_page_requires_auth(): void
    {
        $this->get('/client/ranking')->assertRedirect(route('client.login'));
    }
}
