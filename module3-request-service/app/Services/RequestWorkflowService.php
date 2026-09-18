<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestAttachment;
use App\Models\RequestStatusHistory;
use App\Models\SupportRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class RequestWorkflowService
{
   
    public const TRANSITIONS = [
        'new' => ['received', 'cancelled'],
        'received' => ['in_progress', 'cancelled'],
        'in_progress' => ['resolved', 'cancelled'],
        'resolved' => ['closed', 'in_progress'], // SV xác nhận đóng | SV yêu cầu xử lý lại
        'closed' => [],
        'cancelled' => [],
    ];

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function create(array $data, int $studentId, array $files = []): SupportRequest
    {
        return DB::transaction(function () use ($data, $studentId, $files) {
            $payload = collect($data)->only([
                'department_id',
                'support_type_id',
                'title',
                'content',
                'priority',
            ])->all();

            $request = SupportRequest::create([
                ...$payload,
                'student_id' => $studentId,
                'status' => RequestStatus::New->value,
                'priority' => $payload['priority'] ?? 'normal',
                'code' => $this->generateCode(),
            ]);

            $this->logHistory($request, null, RequestStatus::New->value, $studentId, null);

            $this->storeAttachments($request, $files);

            return $request->load('attachments');
        });
    }

    /**
     * Lưu file vào disk public: request-attachments/{request_id}/...
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function storeAttachments(SupportRequest $request, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('request-attachments/'.$request->id, 'public');

            RequestAttachment::create([
                'request_id' => $request->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }
    }

    /**
     * Đổi trạng thái theo state machine.
     * - Phải đã gán cán bộ trước khi chuyển (trừ cancelled).
     * - resolved = cán bộ xử lý xong, chờ SV phản hồi.
     * - resolved → closed: SV xác nhận đã xong.
     * - resolved → in_progress: SV báo chưa xong, cán bộ xử lý tiếp.
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

        // Bắt buộc gán cán bộ trước khi đổi trạng thái (trừ khi hủy)
        if ($toStatus !== RequestStatus::Cancelled->value && $request->assigned_to === null) {
            throw ValidationException::withMessages([
                'status' => 'Phải gán cán bộ xử lý trước khi đổi trạng thái.',
            ]);
        }

        return DB::transaction(function () use ($request, $from, $toStatus, $changedBy, $note) {
            $request->status = $toStatus;

            if ($toStatus === RequestStatus::Resolved->value) {
                $request->resolved_at = now();
            }
            if ($toStatus === RequestStatus::Closed->value && $request->closed_at === null) {
                $request->closed_at = now();
            }
            // SV yêu cầu xử lý lại → reset mốc resolved để xử lý vòng mới
            if ($from === RequestStatus::Resolved->value && $toStatus === RequestStatus::InProgress->value) {
                $request->resolved_at = null;
            }

            $request->save();

            $this->logHistory($request, $from, $toStatus, $changedBy, $note);

            return $request;
        });
    }

   
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

    
    public function update(SupportRequest $request, array $data, int $changedBy): SupportRequest
    {
        if ($request->status->value !== RequestStatus::New->value) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ được sửa yêu cầu ở trạng thái "Mới tạo".',
            ]);
        }

        return DB::transaction(function () use ($request, $data, $changedBy) {
            $status = RequestStatus::New->value;

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
     
        $sequence = SupportRequest::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return sprintf('YC-%s-%06d', $year, $sequence);
    }
}
