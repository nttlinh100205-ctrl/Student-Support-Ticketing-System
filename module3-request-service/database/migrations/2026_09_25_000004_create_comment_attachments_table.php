<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng comment_attachments — File đính kèm theo từng comment.
 * Cấu trúc tương tự request_attachments nhưng FK tới ticket_comments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id');
            $table->string('original_name', 255);
            $table->string('path', 500);           // relative path trên disk public
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->default(0); // bytes
            $table->timestamps();

            $table->index('comment_id');

            $table->foreign('comment_id')
                ->references('id')
                ->on('ticket_comments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_attachments');
    }
};
