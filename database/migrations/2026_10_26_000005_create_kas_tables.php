<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nama', 60)->default('Kas');
            $table->unsignedBigInteger('saldo_awal')->default(0);
            $table->unsignedBigInteger('batas_minimum')->default(200000);
            $table->timestamps();
        });

        Schema::create('kas_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal')->index();
            $table->string('tipe', 10)->default('keluar');
            $table->string('item', 120);
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('jumlah');
            $table->string('bukti_path')->nullable();
            $table->string('bukti_mime', 100)->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permId = DB::table('permissions')->where('name', 'finance.manage')->value('id');
        if (! $permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name' => 'finance.manage',
                'label' => 'Finance Manage',
                'module' => 'finance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleId = DB::table('roles')->where('name', 'outlet_manager')->value('id');
        if ($roleId && ! DB::table('permission_role')->where('permission_id', $permId)->where('role_id', $roleId)->exists()) {
            DB::table('permission_role')->insert([
                'permission_id' => $permId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transaksi');
        Schema::dropIfExists('kas_settings');
    }
};
