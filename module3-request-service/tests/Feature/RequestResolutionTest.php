<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Services\CommentService;
use App\Services\RequestWorkflowService;
use Tests\TestCase;

class RequestResolutionTest extends TestCase
{
    public function test_each_resolution_requires_a_new_student_reply_even_in_the_same_second(): void
    {
        $this->freezeTime();
        $ticket = SupportRequest::factory()->create(['student_id' => 12, 'assigned_to' => 21, 'status' => 'waiting_info']);
        $comments = app(CommentService::class);
        $workflow = app(RequestWorkflowService::class);
        $comments->addComment($ticket, ['body' => 'Bổ sung hồ sơ.'], 12, 'Sinh viên', 'student');
        $workflow->changeStatus($ticket, 'resolved', 21);
        $this->assertFalse($ticket->fresh()->hasStudentReplySinceResolution());

        $comments->addComment($ticket, ['body' => 'Xác nhận kết quả.'], 12, 'Sinh viên', 'student');
        $this->assertTrue($ticket->fresh()->hasStudentReplySinceResolution());

        $workflow->changeStatus($ticket, 'in_progress', 12);
        $workflow->changeStatus($ticket, 'resolved', 21);
        $this->assertFalse($ticket->fresh()->hasStudentReplySinceResolution());
        $comments->addComment($ticket, ['body' => 'Ghi chú nội bộ.'], 21, 'Cán bộ', 'staff');
        $this->assertFalse($ticket->fresh()->hasStudentReplySinceResolution());
        $comments->addComment($ticket, ['body' => 'Xác nhận kết quả mới.'], 12, 'Sinh viên', 'student');
        $this->assertTrue($ticket->fresh()->hasStudentReplySinceResolution());
    }
}
