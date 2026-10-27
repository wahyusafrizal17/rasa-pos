<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoho_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('running'); // running|success|failed
            $table->string('trigger', 20)->default('cli'); // cli|web|schedule
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('outlets')->default(0);
            $table->unsignedInteger('products')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('removed_count')->default(0);
            $table->unsignedInteger('stocks')->default(0);
            $table->json('details')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoho_sync_runs');
    }
};
