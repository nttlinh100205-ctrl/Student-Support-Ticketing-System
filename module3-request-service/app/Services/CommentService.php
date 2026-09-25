<?php

namespace App\Services;

use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Service xử lý logic nghiệp vụ cho comment thread.
 *
 * Tách riêng khỏi controller để dễ test và tái sử dụng.
 */
class CommentService
{
    /**
     * Tạo comment mới + upload file đính kèm.
     *
     * @param  SupportRequest  $ticket
     * @param  array           $data     ['body', 'is_internal']
     * @param  int             $userId
     * @param  string|null     $userName
     * @param  string          $userRole
     * @param  UploadedFile[]  $files
     * @return TicketComment
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
        // SV không được gửi comment nội bộ
        if (($data['is_internal'] ?? false) && $userRole === 'student') {
            throw ValidationException::withMessages([
                'is_internal' => ['Sinh viên không được gửi bình luận nội bộ.'],
            ]);
        }

        // Ticket đã closed/cancelled thì không cho comment
        $blockedStatuses = ['closed', 'cancelled'];
        if (in_array($ticket->status->value, $blockedStatuses, true)) {
            throw ValidationException::withMessages([
                'request_id' => ['Không thể bình luận vào yêu cầu đã đóng hoặc đã hủy.'],
            ]);
        }

        return DB::transaction(function () use ($ticket, $data, $userId, $userName, $userRole, $files) {
            /** @var TicketComment $comment */
            $comment = $ticket->comments()->create([
                'user_id'     => $userId,
                'user_name'   => $userName,
                'user_role'   => $userRole,
                'body'        => $data['body'],
                'is_internal' => $data['is_internal'] ?? false,
            ]);

            // Upload & tạo bản ghi attachment
            foreach ($files as $file) {
                $path = $file->store(
                    "comments/{$ticket->id}/{$comment->id}",
                    'public'
                );

                $comment->attachments()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }

            $comment->load('attachments');

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
            // Xóa file vật lý trên storage
            foreach ($comment->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->path);
            }

            // DB cascade sẽ xóa attachments record
            $comment->delete();
        });
    }
}
