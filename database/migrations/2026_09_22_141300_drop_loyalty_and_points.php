<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_points');
        Schema::dropIfExists('rewards');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['membership_level', 'points']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'points_value']);
        });

        $permissionIds = DB::table('permissions')->whereIn('name', ['loyalty.view', 'loyalty.manage'])->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        DB::table('settings')->whereIn('key', ['points_earn_per_amount', 'points_redeem_value'])->delete();
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('membership_level')->default('regular');
            $table->unsignedInteger('points')->default(0);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('points_redeemed')->default(0);
            $table->decimal('points_value', 15, 2)->default(0);
        });

        Schema::create('customer_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->integer('points');
            $table->integer('balance_after');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('points_required');
            $table->decimal('value', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
