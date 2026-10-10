<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class TransferAccountSettingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_admin_can_save_transfer_account_and_pos_shows_it(): void
    {
        $this->actingAsAtOutlet($this->admin)
            ->put(route('settings.update'), [
                'tax_rate' => 11,
                'service_charge' => 0,
                'company_name' => 'Rasa',
                'receipt_footer' => 'Terima kasih',
                'qz_printer' => '',
                'transfer_bank' => 'BCA',
                'transfer_account_number' => '1234567890',
                'transfer_account_name' => 'PT Rasa',
            ])
            ->assertRedirect();

        $this->assertSame('BCA', Setting::query()->where('key', 'transfer_bank')->value('value'));

        $this->actingAsAtOutlet($this->cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('BCA')
            ->assertSee('1234567890')
            ->assertSee('PT Rasa');
    }
}
