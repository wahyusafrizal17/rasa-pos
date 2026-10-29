<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('zoho_contact_id')->nullable()->after('order_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('zoho_contact_id')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('zoho_contact_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('zoho_contact_id');
        });
    }
};
