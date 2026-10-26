<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Wahyu',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $user->roles()->sync([Role::query()->where('name', 'super_admin')->firstOrFail()->id]);

        foreach ([
            'tax_rate' => '11',
            'service_charge' => '0',
            'company_name' => 'Rasa',
            'receipt_footer' => 'Terima kasih',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key, 'outlet_id' => null],
                ['value' => $value, 'group' => 'general'],
            );
        }
    }
}
