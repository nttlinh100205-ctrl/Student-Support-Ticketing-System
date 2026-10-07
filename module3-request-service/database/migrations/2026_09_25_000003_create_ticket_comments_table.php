<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng ticket_comments — Comment thread cho mỗi ticket.
 *
 * Mỗi comment thuộc 1 ticket (request_id), ghi nhận:
 * - user_id / user_name / user_role: ai đã gửi
 * - body: nội dung text
 * - is_internal: cờ trao đổi nội bộ (staff ↔ staff), SV không thấy
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');

            // Soft reference sang Module 1 — không FK thật
            $table->unsignedBigInteger('user_id');
            $table->string('user_name', 100)->nullable();      // snapshot tên tại thời điểm gửi
            $table->string('user_role', 30)->default('student'); // student | staff | department_head | admin

            $table->text('body');

            // Cờ trao đổi nội bộ — SV không được xem
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index('request_id');
            $table->index('user_id');

            $table->foreign('request_id')
                ->references('id')
                ->on('requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
