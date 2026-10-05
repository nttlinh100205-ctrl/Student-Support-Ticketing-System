<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Đổi giá trị role sang chữ thường cho khớp JWT (API Contract mục 3.3):
 * STUDENT -> student, STAFF -> staff, DEPARTMENT_HEAD -> department_head, ADMIN -> admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['role' => DB::raw('LOWER(role)')]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->update(['role' => DB::raw('UPPER(role)')]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('STUDENT')->change();
        });
    }
};
