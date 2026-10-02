<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_type_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('support_type_id')
                ->constrained('support_types')
                ->cascadeOnDelete();

            // Mã trường, ví dụ: student_code, copies.
            $table->string('field_key', 50);

            // Tên hiển thị, ví dụ: Mã sinh viên, Số bản.
            $table->string('label', 150);

            // text, textarea, number, date, select, file.
            $table->string('field_type', 20);

            $table->boolean('is_required')->default(false);

            // Danh sách lựa chọn dành cho kiểu select.
            $table->json('options')->nullable();

            $table->string('help_text', 500)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Trong cùng một loại hỗ trợ, mã trường không được trùng.
            $table->unique(
                ['support_type_id', 'field_key'],
                'support_type_fields_type_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_type_fields');
    }
};