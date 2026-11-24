<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('zoho_tax_id', 50)->nullable()->after('zoho_owner');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('zoho_tax_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_amount');
            $table->string('zoho_tax_id', 50)->nullable()->after('tax_rate');
        });

        // order lama: semua item kena tarif pajak order seperti saat dibuat
        DB::table('order_items')->update([
            'tax_rate' => DB::raw('(select orders.tax_rate from orders where orders.id = order_items.order_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'zoho_tax_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['zoho_tax_id', 'tax_rate']);
        });
    }
};
