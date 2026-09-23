<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_addon')->default(false)->after('is_sellable');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('order_id')->constrained('order_items')->cascadeOnDelete();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->unsignedInteger('buy_qty')->default(1)->after('value');
            $table->unsignedInteger('get_qty')->default(1)->after('buy_qty');
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['buy_qty', 'get_qty']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_addon');
        });
    }
};
