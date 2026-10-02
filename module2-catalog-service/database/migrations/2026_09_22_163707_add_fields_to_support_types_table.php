<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_types', function (Blueprint $table) {
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();

            $table->foreignId('department_id')
                ->constrained('support_departments')
                ->restrictOnDelete();

            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('support_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropUnique(['code']);
            $table->dropColumn([
                'name',
                'code',
                'description',
                'is_active',
            ]);
        });
    }
};