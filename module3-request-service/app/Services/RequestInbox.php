<?php

namespace App\Services;

use App\Models\SupportRequest;
use Illuminate\Database\Eloquent\Builder;

class RequestInbox
{
    public function scoped(array $user): Builder
    {
        $query = SupportRequest::query();

        return match ($user['role']) {
            'student' => $query->where('student_id', $user['id']),
            'staff' => $query->where('assigned_to', $user['id']),
            'department_head' => $query->where('department_id', $user['department_id'] ?? -1),
            'admin' => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function labels(array $user): array
    {
        $labels = ['all' => 'Tất cả', 'active' => 'Đang mở'];
        if ($user['role'] === 'student') {
            $labels += ['waiting_info' => 'Cần bổ sung', 'resolved' => 'Chờ xác nhận', 'unrated' => 'Chưa đánh giá'];
        } else {
            $labels += ['new' => 'Chờ tiếp nhận', 'overdue' => 'Quá hạn', 'resolved' => 'Chờ hoàn tất'];
            if (in_array($user['role'], ['admin', 'department_head'], true)) {
                $labels['unassigned'] = 'Chưa phân công';
            }
        }

        return $labels;
    }

    public function apply(Builder $query, string $queue, array $user): Builder
    {
        if (! array_key_exists($queue, $this->labels($user))) {
            return $query;
        }

        return match ($queue) {
            'active' => $query->whereNotIn('status', ['closed', 'cancelled', 'rejected']),
            'waiting_info', 'resolved', 'new' => $query->where('status', $queue),
            'unrated' => $query->where('status', 'closed')->whereNull('rating'),
            'unassigned' => $query->whereNull('assigned_to')->whereNotIn('status', ['closed', 'cancelled', 'rejected']),
            'overdue' => $query->where('sla_deadline_at', '<=', now())->whereNotIn('status', ['resolved', 'closed', 'cancelled', 'rejected']),
            default => $query,
        };
    }

    public function counts(array $user): array
    {
        $counts = [];
        foreach ($this->labels($user) as $key => $label) {
            $counts[$key] = $this->apply($this->scoped($user), $key, $user)->count();
        }

        return $counts;
    }
}
