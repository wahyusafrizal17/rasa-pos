<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_options', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20);
            $table->string('name', 80);
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('group');
        });

        $now = now();
        DB::table('menu_options')->insert([
            ['group' => 'optional', 'name' => 'HOT', 'price' => 0, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'optional', 'name' => 'ICE', 'price' => 0, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'expresso', 'name' => 'None', 'price' => 0, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'expresso', 'name' => '70:30 Blend', 'price' => 5000, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'expresso', 'name' => '50:50 Blend', 'price' => 7000, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'expresso', 'name' => 'Trilogi', 'price' => 10000, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_options');
    }
};
