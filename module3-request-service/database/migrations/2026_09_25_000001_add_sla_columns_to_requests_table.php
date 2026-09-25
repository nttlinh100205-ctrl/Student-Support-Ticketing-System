<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm các cột phục vụ theo dõi SLA:
 *   - sla_deadline_at : mốc deadline tính theo priority
 *   - sla_flag        : on_time | warning | breached
 *
 * Index trên (sla_flag, sla_deadline_at) giúp cron quét ticket nhanh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->timestamp('sla_deadline_at')->nullable()->after('closed_at')
                  ->comment('Mốc deadline SLA, tính từ created_at + thời hạn theo priority');

            $table->string('sla_flag', 20)->default('on_time')->after('sla_deadline_at')
                  ->comment('on_time | warning | breached');

            // Index cho cron quét: WHERE sla_flag != breached AND sla_deadline_at <= ...
            $table->index(['sla_flag', 'sla_deadline_at'], 'requests_sla_check_index');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex('requests_sla_check_index');
            $table->dropColumn(['sla_deadline_at', 'sla_flag']);
        });
    }
};
