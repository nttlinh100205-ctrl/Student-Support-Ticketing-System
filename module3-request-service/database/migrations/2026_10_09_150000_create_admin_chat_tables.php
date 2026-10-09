<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_chat_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('user_name');
            $table->boolean('needs_reply')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('admin_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('admin_chat_threads')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_name');
            $table->boolean('from_admin');
            $table->text('content');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_chat_messages');
        Schema::dropIfExists('admin_chat_threads');
    }
};
