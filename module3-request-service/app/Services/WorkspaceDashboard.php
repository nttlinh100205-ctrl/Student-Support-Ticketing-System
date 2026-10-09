<?php

namespace App\Services;

use App\Models\RequestStatusHistory;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WorkspaceDashboard
{
    public function __construct(private RequestInbox $inbox) {}

    public function data(array $user): array
    {
        $base = $this->inbox->scoped($user);
        $closed = ['closed', 'cancelled', 'rejected'];
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereNotIn('status', $closed)->count(),
            'completed' => (clone $base)->whereIn('status', ['resolved', 'closed'])->count(),
            'unassigned' => (clone $base)->whereNull('assigned_to')->whereNotIn('status', $closed)->count(),
            'overdue' => (clone $base)->whereNotIn('status', [...$closed, 'resolved'])->where('sla_deadline_at', '<', now())->count(),
            'due_soon' => (clone $base)->whereNotIn('status', [...$closed, 'resolved'])->whereBetween('sla_deadline_at', [now(), now()->addDay()])->count(),
            'completed_week' => (clone $base)->where('closed_at', '>=', now()->startOfWeek())->count(),
            'rating' => (clone $base)->whereNotNull('rating')->avg('rating'),
        ];
        $stats['users'] = null;
        if ($user['role'] === 'admin' && ! config('account.fake')) {
            try {
                $stats['users'] = app(ServiceClient::class)->get(config('account.url'), '/api/v1/admin/users')['data']['total'] ?? null;
            } catch (HttpExceptionInterface $e) {
                report($e);
            }
        }
        $daily = (clone $base)->where('created_at', '>=', now()->subDays(13)->startOfDay())->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $trend[] = ['label' => $day->format('d/m'), 'value' => (int) ($daily[$day->format('Y-m-d')] ?? 0)];
        }
        $types = (clone $base)->selectRaw('support_type_id, COUNT(*) as total')->groupBy('support_type_id')->get()->map(fn ($row) => ['label' => config('master_data.support_types.'.$row->support_type_id.'.name', 'Loại #'.$row->support_type_id), 'value' => $row->total]);
        $departments = (clone $base)->selectRaw('department_id, COUNT(*) as total')->groupBy('department_id')->get()->map(fn ($row) => ['label' => config('master_data.departments.'.$row->department_id, 'Phòng #'.$row->department_id), 'value' => $row->total]);

        return compact('stats', 'trend', 'types', 'departments') + [
            'recent' => (clone $base)->latest('updated_at')->limit(6)->get(),
            'activity' => RequestStatusHistory::whereIn('request_id', (clone $base)->select('id'))->with('request')->latest('id')->limit(8)->get(),
            'team' => $this->team($user),
        ];
    }

    public function team(array $user): array
    {
        if (! in_array($user['role'], ['admin', 'department_head'], true)) {
            return [];
        }
        $rows = [];
        $base = $this->inbox->scoped($user);
        $active = (clone $base)->whereNotIn('status', ['closed', 'cancelled', 'rejected'])->selectRaw('assigned_to, COUNT(*) as total')->groupBy('assigned_to')->pluck('total', 'assigned_to');
        $ratings = (clone $base)->whereNotNull('rating')->selectRaw('assigned_to, AVG(rating) as score')->groupBy('assigned_to')->pluck('score', 'assigned_to');
        foreach (config('master_data.staff', []) as $id => $person) {
            if (! is_array($person) || ($person['role'] ?? 'staff') !== 'staff') {
                continue;
            }
            if ($user['role'] === 'department_head' && (int) ($person['department_id'] ?? 0) !== (int) $user['department_id']) {
                continue;
            }
            $rows[] = ['id' => $id, 'name' => $person['full_name'] ?? $person['name'] ?? 'Chưa có tên cán bộ', 'department_id' => $person['department_id'] ?? null, 'active' => (int) ($active[$id] ?? 0), 'rating' => isset($ratings[$id]) ? round($ratings[$id], 1) : null];
        }

        return $rows;
    }
}
