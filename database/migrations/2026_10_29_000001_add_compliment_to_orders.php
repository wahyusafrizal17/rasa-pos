<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_compliment')->default(false)->after('guest_name');
            $table->string('compliment_guest', 80)->nullable()->after('is_compliment');
            $table->string('compliment_reason', 160)->nullable()->after('compliment_guest');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_compliment', 'compliment_guest', 'compliment_reason']);
        });
    }
};
