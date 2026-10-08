<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Department IDs now belong to the catalog service, not this local table.
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereNotNull('department_id')
            ->whereNotIn('department_id', DB::table('support_departments')->select('id'))->exists()) {
            throw new LogicException('Cannot restore local department foreign key while external references exist.');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('support_departments')->nullOnDelete();
        });
    }
};
