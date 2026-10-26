<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->whereIn('name', ['admin', 'captain', 'kitchen', 'bar'])->delete();
    }

    public function down(): void
    {
        $now = now();
        foreach ([
            'admin' => 'Admin',
            'captain' => 'Captain Order',
            'kitchen' => 'Kitchen Checker',
            'bar' => 'Bar Checker',
        ] as $name => $label) {
            DB::table('roles')->insertOrIgnore([
                'name' => $name,
                'label' => $label,
                'description' => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
