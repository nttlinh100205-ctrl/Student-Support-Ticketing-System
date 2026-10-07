<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            // Mã hiển thị cho user — unique, dễ tra cứu
            $table->string('code', 32)->unique();

            // ---- Soft references (Module 1 / Module 2) ----
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('support_type_id');
            $table->unsignedBigInteger('assigned_to')->nullable();

            // ---- Nội dung ----
            $table->string('title', 255);
            $table->text('content');

            $table->string('priority', 20)->default('normal'); // low|normal|high|urgent
            $table->string('status', 30)->default('new');
            // new|received|in_progress|resolved|closed|cancelled

            $table->text('cancelled_reason')->nullable();

            // ---- Mốc thời gian nghiệp vụ (hỗ trợ Report + filter nhanh) ----
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // dễ khôi phục khi xóa nhầm

            $table->index('student_id');
            $table->index(['department_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('status');

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
