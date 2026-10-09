<?php

namespace App\Services;

use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service xử lý logic nghiệp vụ cho comment thread.
 *
 * Tách riêng khỏi controller để dễ test và tái sử dụng.
 */
class CommentService
{
    public function __construct(
        protected RequestWorkflowService $workflow,
    ) {}

    /**
     * Tạo comment mới + upload file đính kèm.
     *
     * @param  array  $data  ['body', 'is_internal']
     * @param  UploadedFile[]  $files
     *
     * @throws ValidationException
     */
    public function addComment(
        SupportRequest $ticket,
        array $data,
        int $userId,
        ?string $userName,
        string $userRole,
        array $files = [],
    ): TicketComment {
        if ($userRole === 'student' && ! in_array($ticket->status->value, ['waiting_info', 'resolved'], true)) {
            throw ValidationException::withMessages([
                'body' => ['Sinh viên chỉ có thể phản hồi khi yêu cầu đang chờ bổ sung hoặc chờ xác nhận kết quả.'],
            ]);
        }

        // Ticket đã closed/cancelled thì không cho comment
        $blockedStatuses = ['closed', 'cancelled', 'rejected'];
        if (in_array($ticket->status->value, $blockedStatuses, true)) {
            throw ValidationException::withMessages([
                'request_id' => ['Không thể bình luận vào yêu cầu đã đóng hoặc đã hủy.'],
            ]);
        }

        return DB::transaction(function () use ($ticket, $data, $userId, $userName, $userRole, $files) {
            /** @var TicketComment $comment */
            $comment = $ticket->comments()->create([
                'user_id' => $userId,
                'user_name' => $userName,
                'user_role' => $userRole,
                'body' => $data['body'],
                'is_internal' => $userRole === 'student' ? false : ($data['is_internal'] ?? false),
            ]);

            // Upload & tạo bản ghi attachment trên disk local/private
            foreach ($files as $file) {
                $path = app(ImageStorage::class)->store($file,
                    "private/comments/{$ticket->id}/{$comment->id}",
                    'local'
                );

                $comment->attachments()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            $comment->load('attachments');

            if ($userRole === 'student' && $ticket->status->value === 'waiting_info') {
                $this->workflow->changeStatus(
                    $ticket,
                    'in_progress',
                    $userId,
                    'Sinh viên đã bổ sung thông tin qua bình luận.',
                );
            }

            return $comment;
        });
    }

    /**
     * Lấy danh sách comment của ticket (có phân trang).
     *
     * SV chỉ thấy comment public, staff/head/admin thấy cả nội bộ.
     */
    public function listComments(SupportRequest $ticket, string $userRole, int $perPage = 20)
    {
        $query = $ticket->comments()
            ->with('attachments')
            ->orderBy('created_at', 'asc');

        // SV không thấy comment nội bộ
        if ($userRole === 'student') {
            $query->public();
        }

        return $query->paginate($perPage);
    }

    /**
     * Xóa comment (chỉ chủ comment hoặc admin).
     *
     * @throws ValidationException
     */
    public function deleteComment(TicketComment $comment, int $userId, string $userRole): void
    {
        $isOwner = $comment->user_id === $userId;

        if (! $isOwner && $userRole !== 'admin') {
            throw ValidationException::withMessages([
                'comment' => ['Bạn không có quyền xóa bình luận này.'],
            ]);
        }

        DB::transaction(function () use ($comment) {
            // Xóa file vật lý trên storage local/private
            foreach ($comment->attachments as $attachment) {
                app(ImageStorage::class)->delete($attachment->path);
            }

            // DB cascade sẽ xóa attachments record
            $comment->delete();
        });
    }

    /**
     * Upload file đính kèm cho comment theo disk local/private.
     */
    public function uploadAttachment(
        SupportRequest $supportRequest,
        TicketComment $comment,
        UploadedFile $file,
        int $userId,
        string $userRole,
        ?int $departmentId = null,
    ): CommentAttachment {
        if ($comment->request_id !== $supportRequest->id) {
            throw ValidationException::withMessages([
                'comment' => ['Bình luận không thuộc yêu cầu này.'],
            ]);
        }

        if (! $this->canAccessRequest($supportRequest, $userId, $userRole, $departmentId)) {
            throw ValidationException::withMessages([
                'request' => ['Bạn không có quyền đính kèm file cho yêu cầu này.'],
            ]);
        }

        $path = app(ImageStorage::class)->store($file, "private/comments/{$supportRequest->id}/{$comment->id}", 'local');

        return $comment->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);
    }

    public function previewAttachment(TicketComment $comment, CommentAttachment $attachment): Response
    {
        if ($attachment->comment_id !== $comment->id) {
            abort(404);
        }

        if (app(ImageStorage::class)->isCloud($attachment->path)) {
            return app(ImageStorage::class)->response($attachment->path, $attachment->original_name);
        }

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
        );
    }

    protected function canAccessRequest(SupportRequest $supportRequest, int $userId, string $userRole, ?int $departmentId = null): bool
    {
        return match ($userRole) {
            'student' => $supportRequest->student_id === $userId,
            'staff' => $supportRequest->assigned_to === $userId,
            'department_head' => $supportRequest->department_id === $departmentId,
            'admin' => true,
            default => false,
        };
    }
}
