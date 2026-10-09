<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Services\ZohoCatalogSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class ZohoCatalogSyncTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
        config([
            'zoho.client_id' => 'cid',
            'zoho.client_secret' => 'secret',
            'zoho.refresh_token' => 'refresh',
            'zoho.organization_id' => '901189625',
            'zoho.token_url' => 'https://accounts.zoho.com/oauth/v2/token',
            'zoho.base_url' => 'https://www.zohoapis.com',
        ]);
    }

    public function test_sync_replaces_demo_outlets_with_zoho_locations_and_catalog(): void
    {
        $this->assertSame('Outlet Bandung', $this->outlet->name);

        Http::fake([
            'accounts.zoho.com/oauth/v2/token' => Http::response([
                'access_token' => 'tok_test',
                'expires_in' => 3600,
            ], 200),
            'www.zohoapis.com/inventory/v1/locations*' => Http::response([
                'code' => 0,
                'locations' => [
                    [
                        'location_id' => 'loc-ho',
                        'location_name' => 'Head Office',
                        'is_location_active' => true,
                        'is_primary_location' => true,
                        'address' => ['city' => 'Tangerang', 'street_address1' => 'Jl. Imam Bonjol'],
                    ],
                    [
                        'location_id' => 'loc-bekasi',
                        'location_name' => 'Hub Bekasi',
                        'is_location_active' => true,
                        'is_primary_location' => false,
                        'address' => ['city' => 'Bekasi', 'street_address1' => ''],
                    ],
                ],
            ], 200),
            'www.zohoapis.com/inventory/v1/items/item-1*' => Http::response([
                'code' => 0,
                'item' => [
                    'item_id' => 'item-1',
                    'name' => '3bt-Peach',
                    'sku' => 'FDA010-PC04',
                    'status' => 'active',
                    'item_type' => 'inventory',
                    'track_inventory' => true,
                    'can_be_sold' => true,
                    'rate' => 100000,
                    'purchase_rate' => 80000,
                    'unit' => 'Pieces',
                    'reorder_level' => 2,
                    'custom_fields' => [
                        ['api_name' => 'cf_category', 'value' => 'Syrup'],
                    ],
                    'locations' => [
                        ['location_id' => 'loc-ho', 'location_stock_on_hand' => 0],
                        ['location_id' => 'loc-bekasi', 'location_stock_on_hand' => 12],
                    ],
                ],
            ], 200),
            'www.zohoapis.com/inventory/v1/items*' => Http::response([
                'code' => 0,
                'items' => [[
                    'item_id' => 'item-1',
                    'name' => '3bt-Peach',
                    'sku' => 'FDA010-PC04',
                    'status' => 'active',
                    'item_type' => 'inventory',
                    'track_inventory' => true,
                    'can_be_sold' => true,
                    'rate' => 100000,
                    'unit' => 'Pieces',
                    'cf_category' => 'Syrup',
                ]],
                'page_context' => ['page' => 1, 'has_more_page' => false],
            ], 200),
        ]);

        $summary = app(ZohoCatalogSync::class)->run(fresh: true);

        $this->assertSame(0, Outlet::query()->where('name', 'Outlet Bandung')->count());
        $this->assertTrue(Outlet::query()->where('zoho_location_id', 'loc-ho')->where('is_central_kitchen', true)->exists());
        $bekasi = Outlet::query()->where('zoho_location_id', 'loc-bekasi')->first();
        $this->assertNotNull($bekasi);
        $this->assertSame('Hub Bekasi', $bekasi->name);

        $this->assertTrue(Category::query()->where('name', 'Syrup')->exists());
        $product = Product::query()->where('zoho_item_id', 'item-1')->first();
        $this->assertNotNull($product);
        $this->assertSame('FDA010-PC04', $product->sku);
        $this->assertEquals(12, (float) Inventory::query()
            ->where('outlet_id', $bekasi->id)
            ->where('product_id', $product->id)
            ->value('quantity'));
        $this->assertGreaterThan(0, $summary['products']);
    }
}
