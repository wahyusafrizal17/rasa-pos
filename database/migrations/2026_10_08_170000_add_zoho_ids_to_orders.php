<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('zoho_salesorder_id')->nullable()->unique()->after('order_number');
            $table->string('zoho_invoice_id')->nullable()->unique()->after('zoho_salesorder_id');
            $table->string('zoho_payment_id')->nullable()->unique()->after('zoho_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['zoho_salesorder_id']);
            $table->dropUnique(['zoho_invoice_id']);
            $table->dropUnique(['zoho_payment_id']);
            $table->dropColumn(['zoho_salesorder_id', 'zoho_invoice_id', 'zoho_payment_id']);
        });
    }
};
