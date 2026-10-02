<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_faqs', function (Blueprint $table) {
            $table->id();

            // Phòng ban phụ trách câu hỏi.
            $table->foreignId('department_id')
                ->constrained('support_departments')
                ->restrictOnDelete();

            // Để trống nếu câu hỏi áp dụng chung cho phòng ban.
            $table->foreignId('support_type_id')
                ->nullable()
                ->constrained('support_types')
                ->restrictOnDelete();

            $table->string('question', 255);

            $table->text('answer');

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index(
                ['department_id', 'is_active'],
                'support_faqs_department_active_index'
            );

            $table->index(
                ['support_type_id', 'is_active'],
                'support_faqs_type_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_faqs');
    }
};