<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->id();

            // Cùng DB với requests → FK thật + cascade khi xóa request
            $table->foreignId('request_id')
                ->constrained('requests')
                ->cascadeOnDelete();

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->unsignedBigInteger('changed_by'); // soft ref → users (Module 1)
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Timeline theo request + thời gian
            $table->index(['request_id', 'created_at']);
            // Thống kê theo loại chuyển trạng thái
            $table->index(['to_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_histories');
    }
};
