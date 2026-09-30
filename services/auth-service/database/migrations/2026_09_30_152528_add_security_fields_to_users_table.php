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
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 150)
                ->after('id');

            $table->string('role', 30)
                ->default('student')
                ->after('email');

            $table->unsignedBigInteger('department_id')
                ->nullable()
                ->after('role');

            $table->string('avatar_path', 500)
                ->nullable()
                ->after('department_id');

            $table->boolean('is_active')
                ->default(true)
                ->after('avatar_path');

            $table->boolean('must_change_password')
                ->default(false)
                ->after('is_active');

            $table->unsignedInteger('failed_login_attempts')
                ->default(0)
                ->after('must_change_password');

            $table->timestamp('locked_until')
                ->nullable()
                ->after('failed_login_attempts');

            $table->index('department_id');
            $table->index('locked_until');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
            $table->dropIndex(['locked_until']);
            $table->dropIndex(['is_active']);

            $table->dropColumn([
                'full_name',
                'role',
                'department_id',
                'avatar_path',
                'is_active',
                'must_change_password',
                'failed_login_attempts',
                'locked_until',
            ]);
        });
    }
};