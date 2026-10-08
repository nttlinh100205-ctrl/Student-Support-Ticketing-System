<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            foreach (['rating_attitude', 'rating_speed', 'rating_quality'] as $column) {
                $table->unsignedTinyInteger($column)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', fn (Blueprint $table) => $table->dropColumn(['rating_attitude', 'rating_speed', 'rating_quality']));
    }
};
