<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Responses\ApiResponse;
use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use App\Services\CommentService;
use Illuminate\Validation\ValidationException;

class AttachmentController extends Controller
{
    public function __construct(
        protected CommentService $commentService,
        protected AuthContext $auth,
    ) {}

    public function store(StoreAttachmentRequest $request, SupportRequest $supportRequest, TicketComment $comment)
    {
        if ($comment->request_id !== $supportRequest->id) {
            return ApiResponse::error('Bình luận không thuộc yêu cầu này.', 404);
        }

        if (! $this->canAccess($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền đính kèm file cho yêu cầu này.', 403);
        }

        try {
            $attachment = $this->commentService->uploadAttachment(
                $supportRequest,
                $comment,
                $request->file('file'),
                $this->auth->userId(),
                $this->auth->role(),
                $this->auth->departmentId(),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success([
            'id' => $attachment->id,
            'comment_id' => $attachment->comment_id,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'preview_url' => route('api.requests.comments.attachments.preview', [
                'supportRequest' => $supportRequest->id,
                'comment' => $comment->id,
                'commentAttachment' => $attachment->id,
            ]),
        ], 'Đã lưu file đính kèm thành công.', 201);
    }

    public function preview(SupportRequest $supportRequest, TicketComment $comment, CommentAttachment $commentAttachment)
    {
        if ($comment->request_id !== $supportRequest->id || $commentAttachment->comment_id !== $comment->id) {
            abort(404);
        }

        if (! $this->canAccess($supportRequest) || ($this->auth->role() === 'student' && $comment->is_internal)) {
            abort(403);
        }

        return $this->commentService->previewAttachment($comment, $commentAttachment);
    }

    protected function canAccess(SupportRequest $supportRequest): bool
    {
        return match ($this->auth->role()) {
            'student' => $supportRequest->student_id === $this->auth->userId(),
            'staff' => $supportRequest->assigned_to === $this->auth->userId(),
            'department_head' => $supportRequest->department_id === $this->auth->departmentId(),
            'admin' => true,
            default => false,
        };
    }
}
