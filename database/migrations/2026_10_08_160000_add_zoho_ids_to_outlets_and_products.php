<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('zoho_location_id')->nullable()->unique()->after('code');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('zoho_item_id')->nullable()->unique()->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropUnique(['zoho_location_id']);
            $table->dropColumn('zoho_location_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['zoho_item_id']);
            $table->dropColumn('zoho_item_id');
        });
    }
};
