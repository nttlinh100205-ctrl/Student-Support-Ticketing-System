<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng lưu lịch sử thông báo SLA đã gửi.
 * Dùng để tránh gửi trùng thông báo cùng loại cho cùng một ticket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('type', 20)->comment('warning | breached');
            $table->unsignedBigInteger('notified_user_id')->nullable()
                  ->comment('User nhận thông báo (assigned_to / department_head)');
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->useCurrent();

            $table->unique(['request_id', 'type'], 'sla_notif_unique');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_notifications');
    }
};
