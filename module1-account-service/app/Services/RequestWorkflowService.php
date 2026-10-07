<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestStatusHistory;
use App\Models\SupportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class RequestWorkflowService
{
   
    public const TRANSITIONS = [
        'new' => ['received', 'cancelled'],
        'received' => ['in_progress', 'cancelled'],
        'in_progress' => ['resolved', 'cancelled'],
        'resolved' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function create(array $data, int $studentId): SupportRequest
    {
        return DB::transaction(function () use ($data, $studentId) {
            $request = SupportRequest::create([
                ...$data,
                'student_id' => $studentId,
                'status' => RequestStatus::New->value,
                'priority' => $data['priority'] ?? 'normal',
                'code' => $this->generateCode(),
            ]);

            $this->logHistory($request, null, RequestStatus::New->value, $studentId, null);

            return $request;
        });
    }

    
    public function changeStatus(SupportRequest $request, string $toStatus, int $changedBy, ?string $note = null): SupportRequest
    {
        $from = $request->status->value;
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (! in_array($toStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Không thể chuyển từ '{$from}' sang '{$toStatus}'.",
            ]);
        }

        return DB::transaction(function () use ($request, $from, $toStatus, $changedBy, $note) {
            $request->status = $toStatus;

            // Ghi mốc thời gian — hỗ trợ Report không cần scan history
            if ($toStatus === RequestStatus::Resolved->value && $request->resolved_at === null) {
                $request->resolved_at = now();
            }
            if ($toStatus === RequestStatus::Closed->value && $request->closed_at === null) {
                $request->closed_at = now();
            }

            $request->save();

            $this->logHistory($request, $from, $toStatus, $changedBy, $note);

            return $request;
        });
    }

    /**
     * Gán cán bộ + ghi assigned_at (lần gán đầu hoặc mỗi lần gán lại).
     */
    public function assign(SupportRequest $request, int $staffId, int $changedBy): SupportRequest
    {
        if (in_array($request->status->value, ['closed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Không thể gán cán bộ cho yêu cầu đã đóng hoặc đã hủy.',
            ]);
        }

        $request->assigned_to = $staffId;
        $request->assigned_at = now();
        $request->save();

        return $request;
    }

    public function cancel(SupportRequest $request, int $changedBy, ?string $reason = null): SupportRequest
    {
        $updated = $this->changeStatus($request, RequestStatus::Cancelled->value, $changedBy, $reason);
        $updated->cancelled_reason = $reason;
        $updated->save();

        return $updated;
    }

    protected function logHistory(SupportRequest $request, ?string $from, string $to, int $changedBy, ?string $note): void
    {
        RequestStatusHistory::create([
            'request_id' => $request->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    protected function generateCode(): string
    {
        $year = now()->format('Y');
        // withTrashed: không tái sử dụng số thứ tự của bản ghi đã soft-delete
        $sequence = SupportRequest::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return sprintf('YC-%s-%06d', $year, $sequence);
    }
}
