<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('sla_days')
                ->nullable()
                ->comment('Số ngày lịch xử lý dự kiến');
        });
    }

    public function down(): void
    {
        Schema::table('support_types', function (Blueprint $table) {
            $table->dropColumn('sla_days');
        });
    }
};
