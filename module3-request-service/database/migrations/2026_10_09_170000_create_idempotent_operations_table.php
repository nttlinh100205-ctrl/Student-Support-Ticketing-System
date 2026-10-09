<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotent_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('scope', 100);
            $table->string('operation_key', 128);
            $table->char('fingerprint', 64);
            $table->unsignedSmallInteger('status')->nullable();
            $table->longText('body')->nullable();
            $table->text('location')->nullable();
            $table->string('content_type')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'scope', 'operation_key'], 'idempotent_operation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotent_operations');
    }
};
