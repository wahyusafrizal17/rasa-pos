<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')->where('method', 'card')->delete();
    }

    public function down(): void
    {
        //
    }
};
