<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestStatusHistory;
use App\Models\SupportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Toàn bộ business logic của Module 3 nằm ở đây (Controller mỏng).
 * State machine + ghi mốc thời gian (assigned_at / resolved_at / closed_at)
 * để Report và filter sau này không phải quét history.
 */
class RequestWorkflowService
{
    /**
     * NƠI DUY NHẤT định nghĩa luồng chuyển trạng thái.
     * Thêm status mới: sửa const này + Enum — không đụng schema DB.
     */
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

    /**
     * Đổi trạng thái theo state machine.
     * Đồng thời ghi mốc resolved_at / closed_at khi chuyển tới các status đó.
     */
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
     * Gán cán bộ + ghi assigned_at.
     * Không đổi status: gán ≠ tiếp nhận (rule B).
     * Status chỉ đổi khi staff/head bấm chuyển trạng thái (new → received → …).
     * → Sinh viên vẫn sửa được khi còn "Mới tạo", kể cả đã được gán cán bộ.
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

    /**
     * Hủy yêu cầu theo state machine.
     * $asStudent = true → chỉ cho hủy khi status ∈ {new, received} (chưa vào in_progress).
     * Admin / staff vẫn theo TRANSITIONS (có thể hủy cả in_progress).
     */
    public function cancel(SupportRequest $request, int $changedBy, ?string $reason = null, bool $asStudent = false): SupportRequest
    {
        if ($asStudent && ! in_array($request->status->value, [
            RequestStatus::New->value,
            RequestStatus::Received->value,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Sinh viên chỉ được hủy yêu cầu khi chưa bắt đầu xử lý (Mới tạo / Đã tiếp nhận).',
            ]);
        }

        $updated = $this->changeStatus($request, RequestStatus::Cancelled->value, $changedBy, $reason);
        $updated->cancelled_reason = $reason;
        $updated->save();

        return $updated;
    }

    /**
     * Sửa nội dung yêu cầu — CHỈ khi trạng thái còn "new".
     * Ghi history audit (from = to = new) kèm note.
     */
    public function update(SupportRequest $request, array $data, int $changedBy): SupportRequest
    {
        if ($request->status->value !== RequestStatus::New->value) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ được sửa yêu cầu ở trạng thái "Mới tạo".',
            ]);
        }

        return DB::transaction(function () use ($request, $data, $changedBy) {
            $status = RequestStatus::New->value;

            // Chỉ cho sửa tiêu đề + nội dung.
            // Không đổi department / support_type / priority → tránh lệch phòng ban đã gán cán bộ.
            $request->fill([
                'title' => $data['title'],
                'content' => $data['content'],
            ]);
            $request->save();

            $this->logHistory(
                $request,
                $status,
                $status,
                $changedBy,
                'Cập nhật tiêu đề / nội dung yêu cầu',
            );

            return $request->fresh();
        });
    }

    /**
     * Xóa yêu cầu (soft delete) — CHỈ khi trạng thái còn "new".
     * Đã tiếp nhận trở đi không được xóa (dùng hủy / đóng thay thế).
     */
    public function delete(SupportRequest $request): void
    {
        if ($request->status->value !== RequestStatus::New->value) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ được xóa yêu cầu ở trạng thái "Mới tạo".',
            ]);
        }

        $request->delete();
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
