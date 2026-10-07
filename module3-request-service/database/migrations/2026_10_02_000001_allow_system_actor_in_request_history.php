<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_status_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('request_status_histories')
            ->whereNull('changed_by')
            ->update(['changed_by' => 0]);

        Schema::table('request_status_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable(false)->change();
        });
    }
};