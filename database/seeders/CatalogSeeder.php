<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'PCS', 'name' => 'Pieces', 'family' => 'count', 'conversion_factor' => 1],
            ['code' => 'POR', 'name' => 'Portion', 'family' => 'count', 'conversion_factor' => 1],
            ['code' => 'KG', 'name' => 'Kilogram', 'family' => 'weight', 'conversion_factor' => 1000],
            ['code' => 'G', 'name' => 'Gram', 'family' => 'weight', 'conversion_factor' => 1],
            ['code' => 'L', 'name' => 'Liter', 'family' => 'volume', 'conversion_factor' => 1000],
            ['code' => 'ML', 'name' => 'Milliliter', 'family' => 'volume', 'conversion_factor' => 1],
        ] as $unit) {
            Unit::query()->updateOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
