<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ảnh / file đính kèm — dùng khi phản ánh Cơ sở vật chất (và mở rộng sau).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('original_name', 255);
            $table->string('path', 500); // relative path trên disk public
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->default(0); // bytes
            $table->timestamps();

            $table->index('request_id');
            $table->foreign('request_id')
                ->references('id')
                ->on('requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
    }
};
