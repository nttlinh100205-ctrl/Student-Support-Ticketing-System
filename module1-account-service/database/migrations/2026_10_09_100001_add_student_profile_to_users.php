<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('student_code', 30)->nullable()->unique();
            $table->string('class_name', 100)->nullable();
            $table->string('faculty', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['student_code', 'class_name', 'faculty', 'deleted_at']));
    }
};
