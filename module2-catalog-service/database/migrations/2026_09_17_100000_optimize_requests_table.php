<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('cancelled_reason');
            }
            if (! Schema::hasColumn('requests', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('assigned_at');
            }
            if (! Schema::hasColumn('requests', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('requests', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->addIndexIfMissing('requests', 'requests_assigned_to_status_index', ['assigned_to', 'status']);
        $this->addIndexIfMissing('requests', 'requests_status_index', ['status']);
        $this->addIndexIfMissing('requests', 'requests_created_at_index', ['created_at']);
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            foreach (['closed_at', 'resolved_at', 'assigned_at'] as $col) {
                if (Schema::hasColumn('requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $exists = DB::selectOne(
                'SELECT 1 AS ok FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
                 LIMIT 1',
                [$table, $indexName]
            );
            if ($exists) {
                return;
            }
        } elseif ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->name ?? '') === $indexName) {
                    return;
                }
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }
};
