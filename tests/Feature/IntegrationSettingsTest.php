<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_admin_can_save_qris_and_zoho_and_config_uses_them(): void
    {
        config([
            'qris.api_key' => 'env-key',
            'zoho.client_id' => 'env-client',
            'zoho.customer_id' => 'env-customer',
        ]);

        $this->actingAsAtOutlet($this->admin)
            ->put(route('settings.update'), [
                'tax_rate' => 11,
                'service_charge' => 0,
                'company_name' => 'Rasa',
                'receipt_footer' => 'Terima kasih',
                'qz_printer' => '',
                'qris_api_key' => 'ui-key',
                'qris_base_url' => 'https://rasaconnect.com',
                'qris_callback_token' => 'ui-token',
                'zoho_client_id' => 'zoho-client',
                'zoho_client_secret' => 'zoho-secret',
                'zoho_refresh_token' => 'zoho-refresh',
                'zoho_organization_id' => 'org-1',
                'zoho_token_url' => 'https://accounts.zoho.com/oauth/v2/token',
                'zoho_base_url' => 'https://www.zohoapis.com',
                'zoho_customer_id' => 'cust-1',
                'zoho_cf_source' => 'POS',
            ])
            ->assertRedirect();

        $this->assertSame('ui-key', Setting::query()->where('key', 'qris_api_key')->value('value'));
        $this->assertSame('zoho-client', Setting::query()->where('key', 'zoho_client_id')->value('value'));
        $this->assertSame('ui-key', config('qris.api_key'));
        $this->assertSame('ui-token', config('qris.callback_token'));
        $this->assertSame('zoho-client', config('zoho.client_id'));
        $this->assertSame('cust-1', config('zoho.customer_id'));
    }

    public function test_empty_integration_settings_keep_env_config(): void
    {
        config(['zoho.client_id' => 'env-client', 'qris.api_key' => 'env-key']);

        apply_integration_settings();

        $this->assertSame('env-client', config('zoho.client_id'));
        $this->assertSame('env-key', config('qris.api_key'));
    }
}
