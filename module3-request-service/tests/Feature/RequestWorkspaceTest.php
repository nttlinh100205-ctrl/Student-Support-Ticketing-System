<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Services\RequestInbox;
use Tests\TestCase;

class RequestWorkspaceTest extends TestCase
{
    private function loginAs(string $role, int $id, ?int $department = null): static
    {
        return $this->withSession(['fake_user' => ['role' => $role, 'id' => $id, 'department_id' => $department, 'full_name' => 'Người kiểm thử']]);
    }

    public function test_student_queues_and_counts_exclude_other_students(): void
    {
        $mine = SupportRequest::factory()->create(['student_id' => 12, 'status' => 'waiting_info']);
        SupportRequest::factory()->create(['student_id' => 13, 'status' => 'waiting_info']);
        SupportRequest::factory()->create(['student_id' => 12, 'status' => 'closed', 'rating' => null]);

        $this->loginAs('student', 12)->get('/requests?queue=waiting_info')->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $mine->id)
            ->assertViewHas('queueCounts', fn ($counts) => $counts['waiting_info'] === 1 && $counts['unrated'] === 1 && $counts['all'] === 2);
    }

    public function test_head_unassigned_queue_and_export_stay_within_department(): void
    {
        $mine = SupportRequest::factory()->create(['department_id' => 3, 'assigned_to' => null, 'status' => 'new']);
        $other = SupportRequest::factory()->create(['department_id' => 2, 'assigned_to' => null, 'status' => 'new']);
        SupportRequest::factory()->create(['department_id' => 3, 'assigned_to' => 21, 'status' => 'new']);

        $this->loginAs('department_head', 31, 3)->get('/requests?queue=unassigned')->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $mine->id);
        $csv = $this->get('/requests/export?queue=unassigned&format=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString($mine->code, $csv);
        $this->assertStringNotContainsString($other->code, $csv);
    }

    public function test_overdue_queue_uses_deadline_and_excludes_finished_requests(): void
    {
        $overdue = SupportRequest::factory()->create(['assigned_to' => 21, 'status' => 'in_progress', 'sla_flag' => 'on_time', 'sla_deadline_at' => now()->subHour()]);
        SupportRequest::factory()->create(['assigned_to' => 21, 'status' => 'closed', 'sla_deadline_at' => now()->subDay()]);
        SupportRequest::factory()->create(['assigned_to' => 22, 'status' => 'new', 'sla_deadline_at' => now()->subHour()]);

        $this->loginAs('staff', 21, 3)->get('/requests?queue=overdue&sort=deadline')->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $overdue->id);
        $this->assertSame(0, app(RequestInbox::class)->scoped(['role' => 'unknown', 'id' => 1])->count());
    }

    public function test_full_web_flow_from_submission_to_feedback_and_rating(): void
    {
        $this->freezeTime();
        $this->loginAs('student', 12)->post('/requests', [
            'department_id' => 3, 'support_type_id' => 5,
            'title' => 'Xin hỗ trợ thủ tục vay vốn học tập',
            'content' => 'Em cần hướng dẫn hồ sơ vay vốn học tập cho học kỳ này.', 'priority' => 'normal',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $ticket = SupportRequest::firstOrFail();
        $this->assertSame(12, $ticket->student_id);

        $this->loginAs('department_head', 31, 3)->put("/requests/{$ticket->id}/assign", ['assigned_to' => 21])->assertSessionHas('success');
        foreach (['received', 'in_progress', 'waiting_info'] as $status) {
            $this->loginAs('staff', 21, 3)->put("/requests/{$ticket->id}/status", ['status' => $status, 'note' => 'Cần bổ sung thông tin học kỳ.'])->assertSessionHas('success');
            $this->assertSame($status, $ticket->fresh()->status->value);
        }
        $this->loginAs('student', 12)->post("/requests/{$ticket->id}/comments", ['body' => 'Em bổ sung thông tin học kỳ một, năm học hiện tại.'])->assertSessionHas('success');
        $this->assertSame('in_progress', $ticket->fresh()->status->value);

        $this->loginAs('staff', 21, 3)->put("/requests/{$ticket->id}/status", ['status' => 'resolved', 'note' => 'Đã hướng dẫn đầy đủ hồ sơ.'])->assertSessionHas('success');
        $this->assertFalse($ticket->fresh()->hasStudentReplySinceResolution());
        $this->put("/requests/{$ticket->id}/status", ['status' => 'closed'])->assertSessionHas('error');
        $this->assertSame('resolved', $ticket->fresh()->status->value);
        $this->loginAs('student', 12)->get("/requests/{$ticket->id}")->assertOk()->assertSee('Xác nhận kết quả phù hợp');
        $this->post("/requests/{$ticket->id}/comments", ['body' => 'Tôi xác nhận kết quả xử lý đáp ứng yêu cầu.'])->assertSessionHas('success');
        $this->loginAs('staff', 21, 3)->put("/requests/{$ticket->id}/status", ['status' => 'closed'])->assertSessionHas('success');
        $this->loginAs('student', 12)->post("/requests/{$ticket->id}/rating", ['rating' => 5, 'rating_comment' => 'Hỗ trợ rõ ràng.'])->assertSessionHas('success');
        $this->assertSame(5, $ticket->fresh()->rating);
        $this->assertGreaterThanOrEqual(7, $ticket->statusHistories()->count());
    }
}
