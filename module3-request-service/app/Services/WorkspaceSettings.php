<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class WorkspaceSettings
{
    public function get(string $key): int
    {
        return (int) (DB::table('workspace_settings')->where('key', $key)->value('value') ?? config('sla.'.$key));
    }

    public function all(): array
    {
        $values = [];
        foreach (['deadline_hours.low', 'deadline_hours.normal', 'deadline_hours.high', 'deadline_hours.urgent', 'warning_threshold_percent'] as $key) {
            $values[$key] = $this->get($key);
        }

return $values;
    }

    public function save(array $values, int $actor): void
    {
        DB::transaction(function () use ($values, $actor) {
            foreach ($values as $key => $value) {
                DB::table('workspace_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_by' => $actor, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
}
