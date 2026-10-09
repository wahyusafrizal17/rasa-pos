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
        $password = Hash::make('password');

        foreach ([
            ['name' => 'Wahyu', 'email' => 'admin@example.com', 'role' => 'super_admin'],
            ['name' => 'Rina Manager', 'email' => 'manager@example.com', 'role' => 'outlet_manager'],
            ['name' => 'Dina Cashier', 'email' => 'cashier@example.com', 'role' => 'cashier'],
            ['name' => 'Budi Kitchen', 'email' => 'kitchen@example.com', 'role' => 'kitchen'],
            ['name' => 'Andi Captain', 'email' => 'captain@example.com', 'role' => 'captain'],
            ['name' => 'Sari Bar', 'email' => 'bar@example.com', 'role' => 'bar'],
        ] as $def) {
            $user = User::query()->updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
            $user->roles()->sync([Role::query()->where('name', $def['role'])->firstOrFail()->id]);
        }

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
