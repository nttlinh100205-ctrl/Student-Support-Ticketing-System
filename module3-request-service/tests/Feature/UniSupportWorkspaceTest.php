<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Services\SlaService;
use Database\Seeders\UniSupportDemoSeeder;
use Tests\TestCase;

class UniSupportWorkspaceTest extends TestCase
{
    private function role(string $role, int $id = 12, ?int $dept = 3): static
    {
        return $this->withSession(['fake_user' => ['role' => $role, 'id' => $id, 'department_id' => $dept, 'full_name' => 'Người kiểm thử']]);
    }

    public function test_dashboards_render_with_scoped_statistics(): void
    {
        SupportRequest::factory()->create(['student_id' => 12, 'department_id' => 3, 'assigned_to' => 21]);
        SupportRequest::factory()->create(['student_id' => 13, 'department_id' => 2, 'assigned_to' => 22]);
        foreach ([['student', 12, 1], ['staff', 21, 1], ['department_head', 31, 1], ['admin', 1, 2]] as [$role,$id,$count]) {
            $this->role($role, $id)->get('/dashboard')->assertOk()->assertViewHas('stats', fn ($s) => $s['total'] === $count);
        }
    }

    public function test_role_pages_are_protected(): void
    {
        $this->role('student')->get('/kanban')->assertForbidden();
        $this->get('/team')->assertForbidden();
        $this->get('/admin/audit')->assertForbidden();
        $this->role('staff', 21)->get('/kanban')->assertOk();
        $this->get('/ratings')->assertOk();
        $this->get('/team')->assertForbidden();
        $this->role('department_head', 31)->get('/team')->assertOk();
        $this->role('admin', 1)->get('/admin/audit')->assertOk();
    }

    public function test_assignment_saves_deadline_priority_and_rejection_requires_reason(): void
    {
        $ticket = SupportRequest::factory()->create(['department_id' => 3, 'assigned_to' => null, 'status' => 'new']);
        $this->role('department_head', 31)->put("/requests/{$ticket->id}/assign", ['assigned_to' => 21, 'priority' => 'urgent', 'sla_deadline_at' => now()->addDays(2)->format('Y-m-d H:i:s'), 'note' => 'Ưu tiên hồ sơ này'])->assertSessionHas('success');
        $this->assertSame('urgent', $ticket->fresh()->priority->value);
        $this->role('staff', 21)->put("/requests/{$ticket->id}/status", ['status' => 'rejected'])->assertSessionHas('error');
        $this->assertSame('new', $ticket->fresh()->status->value);
        $this->put("/requests/{$ticket->id}/status", ['status' => 'rejected', 'note' => 'Hồ sơ không thuộc phạm vi hỗ trợ.'])->assertSessionHas('success');
        $this->assertSame('rejected', $ticket->fresh()->status->value);
        $this->get("/requests/{$ticket->id}")->assertOk()->assertSee('Từ chối');
    }

    public function test_rating_criteria_are_saved_only_for_owner(): void
    {
        $ticket = SupportRequest::factory()->create(['student_id' => 12, 'status' => 'closed', 'rating' => null]);
        $payload = ['rating' => 4, 'rating_attitude' => 5, 'rating_speed' => 3, 'rating_quality' => 4];
        $this->role('student', 13)->post("/requests/{$ticket->id}/rating", $payload)->assertSessionHas('error');
        $this->assertNull($ticket->fresh()->rating);
        $this->role('student', 12)->post("/requests/{$ticket->id}/rating", $payload)->assertSessionHas('success');
        $this->assertSame(3, $ticket->fresh()->rating_speed);
    }

    public function test_settings_are_admin_only_and_change_future_deadlines(): void
    {
        $values = ['low' => 80, 'normal' => 30, 'high' => 10, 'urgent' => 5, 'warning' => 70];
        $this->role('staff', 21)->put('/admin/settings', $values)->assertForbidden();
        $this->role('admin', 1)->put('/admin/settings', $values)->assertSessionHas('success');
        $this->get('/admin/settings')->assertOk();
        $now = now();
        $this->assertTrue(app(SlaService::class)->calculateDeadline('urgent', $now)->equalTo($now->copy()->addHours(5)));
    }

    public function test_unassignment_and_print_report_are_scoped_to_department(): void
    {
        $ticket = SupportRequest::factory()->create(['department_id' => 3, 'assigned_to' => 21, 'status' => 'received']);
        $other = SupportRequest::factory()->create(['department_id' => 2, 'assigned_to' => 22, 'status' => 'received']);
        $this->role('department_head', 31)->delete("/requests/{$other->id}/assignment", ['note' => 'Điều phối lại'])->assertNotFound();
        $this->delete("/requests/{$ticket->id}/assignment", ['note' => 'Điều phối lại'])->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->assigned_to);
        $this->get('/requests/export?format=print')->assertOk()->assertSee('In / Lưu PDF')->assertSee($ticket->code)->assertDontSee($other->code);
    }

    public function test_demo_seeder_is_repeatable_without_overwriting(): void
    {
        request()->attributes->set('account_user', ['id' => 12, 'role' => 'student', 'email' => 'student@support.test']);
        $this->seed(UniSupportDemoSeeder::class);
        $count = SupportRequest::count();
        $first = SupportRequest::firstOrFail();
        $first->update(['title' => 'Nội dung người dùng đã sửa']);
        $this->seed(UniSupportDemoSeeder::class);
        $this->assertSame($count, SupportRequest::count());
        $this->assertSame('Nội dung người dùng đã sửa', $first->fresh()->title);
    }
}
