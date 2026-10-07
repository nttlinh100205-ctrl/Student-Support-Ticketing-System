<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use App\Services\CommentService;
use Illuminate\Validation\ValidationException;

/**
 * API Controller — Trao đổi (Comment Thread) trên ticket.
 *
 * Endpoints:
 *   GET    /api/requests/{supportRequest}/comments          → index
 *   POST   /api/requests/{supportRequest}/comments          → store
 *   DELETE /api/requests/{supportRequest}/comments/{comment} → destroy
 */
class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService,
        protected AuthContext $auth,
    ) {
    }

    /**
     * GET /api/requests/{supportRequest}/comments
     *
     * Danh sách comment (phân trang, sắp cũ → mới).
     * SV chỉ thấy comment công khai, staff/head/admin thấy cả nội bộ.
     */
    public function index(SupportRequest $supportRequest)
    {
        if (! $this->canView($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền xem trao đổi của yêu cầu này.', 403);
        }

        $comments = $this->commentService->listComments(
            $supportRequest,
            $this->auth->role(),
        );

        return ApiResponse::success(
            TicketCommentResource::collection($comments)
        );
    }

    /**
     * POST /api/requests/{supportRequest}/comments
     *
     * Tạo bình luận mới, kèm file đính kèm (tùy chọn).
     *
     * Body (multipart/form-data):
     *   - body (string, required): Nội dung bình luận
     *   - is_internal (bool, optional): Cờ nội bộ (chỉ staff/head/admin)
     *   - attachments[] (file[], optional): Tối đa 5 file, mỗi file ≤ 10 MB
     */
    public function store(StoreCommentRequest $request, SupportRequest $supportRequest)
    {
        if (! $this->canView($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền bình luận vào yêu cầu này.', 403);
        }

        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        try {
            $comment = $this->commentService->addComment(
                ticket:   $supportRequest,
                data:     $request->safe()->only(['body', 'is_internal']),
                userId:   $this->auth->userId(),
                userName: $this->auth->fullName(),
                userRole: $this->auth->role(),
                files:    $files,
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success(
            new TicketCommentResource($comment),
            'Đã thêm bình luận thành công.',
            201
        );
    }

    /**
     * DELETE /api/requests/{supportRequest}/comments/{comment}
     *
     * Xóa comment (chỉ chủ comment hoặc admin).
     */
    public function destroy(SupportRequest $supportRequest, TicketComment $comment)
    {
        // Đảm bảo comment thuộc ticket
        if ($comment->request_id !== $supportRequest->id) {
            return ApiResponse::error('Bình luận không thuộc yêu cầu này.', 404);
        }

        try {
            $this->commentService->deleteComment(
                $comment,
                $this->auth->userId(),
                $this->auth->role(),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        return ApiResponse::success(null, 'Đã xóa bình luận thành công.');
    }

    /**
     * Kiểm tra quyền xem — logic giống RequestController::canView().
     */
    protected function canView(SupportRequest $supportRequest): bool
    {
        return match ($this->auth->role()) {
            'student'         => $supportRequest->student_id === $this->auth->userId(),
            'staff'           => $supportRequest->assigned_to === $this->auth->userId(),
            'department_head' => $supportRequest->department_id === $this->auth->departmentId(),
            'admin'           => true,
            default           => false,
        };
    }
}
