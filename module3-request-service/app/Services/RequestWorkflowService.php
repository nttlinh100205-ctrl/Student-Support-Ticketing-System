<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\SlaFlag;
use App\Models\RequestAttachment;
use App\Models\RequestStatusHistory;
use App\Models\SupportRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestWorkflowService
{
    public function __construct(
        protected SlaService $slaService,
    ) {}

    public const TRANSITIONS = [
        'new' => ['received', 'cancelled', 'rejected'],
        'received' => ['in_progress', 'cancelled', 'rejected'],
        'in_progress' => ['waiting_info', 'resolved', 'cancelled', 'rejected'],
        'waiting_info' => ['in_progress', 'cancelled', 'rejected'],
        'resolved' => ['closed', 'in_progress'], // staff đóng sau phản hồi | SV yêu cầu xử lý lại
        'closed' => ['in_progress'], // SV yêu cầu mở lại để staff tiếp tục
        'cancelled' => [],
        'rejected' => [],
    ];

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function create(array $data, int $studentId, array $files = []): SupportRequest
    {
        $formData = app(RequestFormData::class)->validate((int) $data['support_type_id'], $data['form_values'] ?? [], $data['form_files'] ?? []);

        return DB::transaction(function () use ($data, $studentId, $files, $formData) {
            $payload = collect($data)->only([
                'department_id',
                'support_type_id',
                'title',
                'content',
                'priority',
            ])->all();

            $priority = $payload['priority'] ?? 'normal';
            $createdAt = now();
            $assignedTo = $this->selectStaffForDepartment((int) $payload['department_id']);

            $request = SupportRequest::create([
                ...$payload,
                'student_id' => $studentId,
                'assigned_to' => $assignedTo,
                'assigned_at' => $assignedTo ? $createdAt : null,
                'status' => RequestStatus::New->value,
                'priority' => $priority,
                'code' => $this->generateCode(),
                'sla_deadline_at' => ($slaDays = config('master_data.support_types.'.$payload['support_type_id'].'.sla_days')) !== null
                    ? $createdAt->copy()->addDays((int) $slaDays)
                    : $this->slaService->calculateDeadline($priority, $createdAt),
                'sla_flag' => SlaFlag::OnTime->value,
            ]);

            $historyNote = $assignedTo ? "Tự động phân công cán bộ #{$assignedTo}." : null;
            $this->logHistory($request, null, RequestStatus::New->value, $studentId, $historyNote);

            $this->storeAttachments($request, $files);

            $request->form_data = array_map(function ($field) use ($request) {
                $upload = $field['upload'];
                unset($field['upload']);
                if ($upload instanceof UploadedFile) {
                    $field['path'] = $upload->store('request-forms/'.$request->id, 'local');
                }

                return $field;
            }, $formData);
            $request->save();

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
     * - resolved → closed: cán bộ đóng sau khi sinh viên phản hồi.
     * - resolved/closed → in_progress: sinh viên yêu cầu xử lý tiếp.
     */
    public function changeStatus(SupportRequest $request, string $toStatus, int $changedBy, ?string $note = null): SupportRequest
    {
        if ($toStatus === 'rejected' && trim($note ?? '') === '') {
            throw ValidationException::withMessages(['note' => 'Vui lòng nêu lý do từ chối yêu cầu.']);
        }
        $from = $request->status->value;
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (! in_array($toStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Không thể chuyển từ '{$from}' sang '{$toStatus}'.",
            ]);
        }

        if ($from === RequestStatus::Resolved->value
            && $toStatus === RequestStatus::Closed->value
            && ! $request->hasStudentReplySinceResolution()) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể đóng yêu cầu sau khi sinh viên đã phản hồi kết quả xử lý.',
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
                $request->resolution_comment_boundary = $request->comments()->max('id') ?? 0;
            }
            if ($toStatus === RequestStatus::Closed->value && $request->closed_at === null) {
                $request->closed_at = now();
            }
            if ($from === RequestStatus::Closed->value && $toStatus === RequestStatus::InProgress->value) {
                $request->closed_at = null;
                $request->resolved_at = null;
                $request->sla_deadline_at = $this->slaService->calculateDeadline($request->priority->value, now());
                $request->sla_flag = SlaFlag::OnTime->value;
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

    public function assign(SupportRequest $request, int $staffId, int $changedBy, array $options = []): SupportRequest
    {
        return DB::transaction(function () use ($request, $staffId, $changedBy, $options) {
            if (in_array($request->status->value, ['closed', 'cancelled', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể gán cán bộ cho yêu cầu đã đóng hoặc đã hủy.',
                ]);
            }

            if (! in_array($staffId, config("master_data.staff_by_department.{$request->department_id}", []), true)) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Cán bộ được chọn không thuộc phòng ban đang xử lý yêu cầu.',
                ]);
            }

            if (! empty($options['sla_deadline_at'])) {
                $request->sla_deadline_at = $options['sla_deadline_at'];
                $request->sla_flag = SlaFlag::OnTime;
            }
            if (! empty($options['priority'])) {
                $request->priority = $options['priority'];
            }
            $request->assigned_to = $staffId;
            $request->assigned_at = now();
            $request->save();

            $status = $request->status->value;
            $this->logHistory(
                $request,
                $status,
                $status,
                $changedBy,
                trim("Gán cán bộ xử lý #{$staffId}. ".($options['note'] ?? '')),
            );

            return $request->fresh();
        });
    }

    public function transfer(SupportRequest $request, int $departmentId, int $supportTypeId, int $changedBy): SupportRequest
    {
        if (in_array($request->status->value, ['closed', 'cancelled', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể chuyển yêu cầu chưa đóng hoặc chưa hủy.',
            ]);
        }

        $departments = config('master_data.departments', []);
        $supportTypes = config('master_data.support_types', []);
        if (! isset($departments[$departmentId])
            || ! isset($supportTypes[$supportTypeId])
            || (int) $supportTypes[$supportTypeId]['department_id'] !== $departmentId) {
            throw ValidationException::withMessages([
                'department_id' => 'Phòng ban hoặc loại hỗ trợ đích không hợp lệ.',
            ]);
        }

        if ($request->department_id === $departmentId) {
            throw ValidationException::withMessages([
                'department_id' => 'Vui lòng chọn phòng ban khác để chuyển tiếp.',
            ]);
        }

        return DB::transaction(function () use ($request, $departmentId, $supportTypeId, $changedBy, $departments) {
            $fromDepartmentId = $request->department_id;
            $request->department_id = $departmentId;
            $request->support_type_id = $supportTypeId;
            $request->assigned_to = $this->selectStaffForDepartment($departmentId);
            $request->assigned_at = $request->assigned_to ? now() : null;
            $request->save();

            $note = sprintf(
                'Chuyển yêu cầu từ %s sang %s.%s',
                $departments[$fromDepartmentId] ?? "phòng #{$fromDepartmentId}",
                $departments[$departmentId],
                $request->assigned_to ? " Tự động phân công cán bộ #{$request->assigned_to}." : '',
            );
            $status = $request->status->value;
            $this->logHistory($request, $status, $status, $changedBy, $note);

            return $request->fresh();
        });
    }

    public function findPotentialDuplicates(array $data, int $studentId)
    {
        $title = Str::lower(Str::squish($data['title']));
        $tokens = collect(explode(' ', $title))->filter(fn ($token) => mb_strlen($token) >= 3)->unique();
        if ($tokens->isEmpty()) {
            return collect();
        }

        return SupportRequest::query()
            ->where('student_id', $studentId)
            ->where('department_id', $data['department_id'])
            ->whereNotIn('status', ['closed', 'cancelled', 'rejected'])
            ->latest()
            ->limit(100)
            ->get(['id', 'code', 'title', 'status', 'created_at'])
            ->map(function (SupportRequest $candidate) use ($title) {
                similar_text($title, Str::lower(Str::squish($candidate->title)), $similarity);
                $candidate->setAttribute('title_similarity', $similarity);

                return $candidate;
            })
            ->filter(fn (SupportRequest $candidate) => $candidate->title_similarity >= 75)
            ->take(3)
            ->values();
    }

    protected function selectStaffForDepartment(int $departmentId): ?int
    {
        if (! config('account.fake') && ! request()->attributes->has('account_user')) {
            return null;
        }
        $staffIds = config("master_data.staff_by_department.{$departmentId}", []);
        if ($staffIds === []) {
            return null;
        }

        return collect($staffIds)
            ->map(fn ($staffId) => [
                'id' => (int) $staffId,
                'load' => SupportRequest::query()
                    ->where('assigned_to', $staffId)
                    ->whereNotIn('status', ['closed', 'cancelled', 'rejected'])
                    ->count(),
            ])
            ->sortBy(['load', 'id'])
            ->first()['id'];
    }

    public function assignOverdueUnassigned(bool $dryRun = false): int
    {
        $cutoff = now()->subDay();
        $excludedStatuses = config('sla.excluded_statuses', ['resolved', 'closed', 'cancelled', 'rejected']);
        $assignedCount = 0;

        SupportRequest::query()
            ->whereNull('assigned_to')
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '<=', $cutoff)
            ->whereNotIn('status', $excludedStatuses)
            ->chunkById(200, function ($tickets) use ($cutoff, $excludedStatuses, $dryRun, &$assignedCount) {
                foreach ($tickets as $ticket) {
                    $assigned = DB::transaction(function () use ($ticket, $cutoff, $excludedStatuses, $dryRun) {
                        $lockedTicket = SupportRequest::query()
                            ->lockForUpdate()
                            ->find($ticket->id);

                        if (! $lockedTicket
                            || $lockedTicket->assigned_to !== null
                            || ! $lockedTicket->sla_deadline_at
                            || $lockedTicket->sla_deadline_at->gt($cutoff)
                            || in_array($lockedTicket->status->value, $excludedStatuses, true)) {
                            return false;
                        }

                        $staffId = $this->selectStaffForDepartment($lockedTicket->department_id);
                        if ($staffId === null) {
                            return false;
                        }

                        if ($dryRun) {
                            return true;
                        }

                        $status = $lockedTicket->status->value;
                        $lockedTicket->assigned_to = $staffId;
                        $lockedTicket->assigned_at = now();
                        $lockedTicket->save();

                        $this->logHistory(
                            $lockedTicket,
                            $status,
                            $status,
                            null,
                            "Tự động phân công cán bộ #{$staffId} do ticket quá hạn SLA hơn 24 giờ.",
                        );

                        return true;
                    });

                    if ($assigned) {
                        $assignedCount++;
                    }
                }
            });

        return $assignedCount;
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

    protected function logHistory(SupportRequest $request, ?string $from, string $to, ?int $changedBy, ?string $note): void
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
