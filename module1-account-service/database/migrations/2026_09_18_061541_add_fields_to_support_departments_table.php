<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('support_departments', 'name')) {
            Schema::table('support_departments', function (Blueprint $table) {
                $table->string('name', 100)
                    ->default('Phòng Hỗ trợ Sinh viên')
                    ->after('id');
            });
        }

        if (!Schema::hasColumn('support_departments', 'code')) {
            Schema::table('support_departments', function (Blueprint $table) {
                $table->string('code', 50)
                    ->default('SUPPORT')
                    ->unique()
                    ->after('name');
            });
        }

        if (!Schema::hasColumn('support_departments', 'description')) {
            Schema::table('support_departments', function (Blueprint $table) {
                $table->text('description')
                    ->nullable()
                    ->after('code');
            });
        }

        if (!Schema::hasColumn('support_departments', 'is_active')) {
            Schema::table('support_departments', function (Blueprint $table) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không xóa các cột đã tồn tại từ trước.
    }
};