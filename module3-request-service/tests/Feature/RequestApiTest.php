<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Models\TicketComment;
use App\Services\RequestWorkflowService;
use App\Services\SlaService;
use Tests\TestCase;

class RequestApiTest extends TestCase
{
    public function test_api_requires_auth_headers(): void
    {
        $response = $this->getJson('/api/requests');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_list_own_requests_with_fake_auth_headers(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 101,
            'X-User-Role' => 'student',
        ])->getJson('/api/requests');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_student_can_reopen_a_closed_request_for_staff_to_continue(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'status' => 'closed',
            'closed_at' => now(),
            'sla_deadline_at' => now()->subHour(),
            'sla_flag' => 'breached',
        ]);

        $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->putJson("/api/requests/{$request->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'in_progress',
            'closed_at' => null,
            'sla_flag' => 'on_time',
        ]);
        $this->assertTrue($request->fresh()->sla_deadline_at->isFuture());
    }

    public function test_staff_cannot_change_status_of_request_assigned_to_another_staff_member(): void
    {
        $request = SupportRequest::factory()->create([
            'assigned_to' => 22,
            'status' => 'new',
        ]);

        $this->withHeaders([
            'X-User-Id' => 21,
            'X-User-Role' => 'staff',
        ])->putJson("/api/requests/{$request->id}/status", ['status' => 'received'])
            ->assertForbidden();

        $this->assertSame('new', $request->fresh()->status->value);
    }

    public function test_student_cannot_close_resolved_request_but_can_request_rework(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'status' => 'resolved',
            'resolved_at' => now()->subMinute(),
        ]);
        $headers = ['X-User-Id' => 12, 'X-User-Role' => 'student'];

        $this->withHeaders($headers)->putJson("/api/requests/{$request->id}/status", ['status' => 'closed'])
            ->assertForbidden();
        $this->withHeaders($headers)->putJson("/api/requests/{$request->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_staff_can_close_resolved_request_after_student_public_reply(): void
    {
        $request = SupportRequest::factory()->create([
            'assigned_to' => 21,
            'status' => 'resolved',
            'resolved_at' => now()->subMinute(),
        ]);
        $headers = ['X-User-Id' => 21, 'X-User-Role' => 'staff'];

        $this->withHeaders($headers)->putJson("/api/requests/{$request->id}/status", ['status' => 'closed'])
            ->assertStatus(409);

        TicketComment::create([
            'request_id' => $request->id,
            'user_id' => 12,
            'user_name' => 'Sinh viên',
            'user_role' => 'student',
            'body' => 'Em đã kiểm tra, cảm ơn cán bộ.',
            'is_internal' => false,
            'created_at' => now(),
        ]);

        $this->withHeaders($headers)->putJson("/api/requests/{$request->id}/status", ['status' => 'closed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_department_head_cannot_change_status_even_in_their_department(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => 21,
            'status' => 'new',
        ]);

        $this->withHeaders([
            'X-User-Id' => 31,
            'X-User-Role' => 'department_head',
            'X-Department-Id' => 3,
        ])->putJson("/api/requests/{$request->id}/status", ['status' => 'received'])
            ->assertForbidden();
    }

    public function test_staff_cannot_change_status_of_another_staff_members_request_in_web(): void
    {
        $request = SupportRequest::factory()->create([
            'assigned_to' => 22,
            'status' => 'new',
        ]);

        $this->withSession(['fake_user' => [
            'id' => 21,
            'role' => 'staff',
            'department_id' => 3,
            'full_name' => 'Cán bộ',
        ]])->put(route('requests.update-status', $request), ['status' => 'received'])
            ->assertSessionHas('error');

        $this->assertSame('new', $request->fresh()->status->value);
    }

    public function test_student_can_update_only_title_and_content_of_new_request(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->putJson("/api/requests/{$request->id}", [
            'title' => 'Tiêu đề yêu cầu đã cập nhật',
            'content' => 'Nội dung yêu cầu được cập nhật đầy đủ hơn.',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Tiêu đề yêu cầu đã cập nhật');

        $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->putJson("/api/requests/{$request->id}", [
            'title' => 'Tiêu đề yêu cầu đã cập nhật',
            'content' => 'Nội dung yêu cầu được cập nhật đầy đủ hơn.',
            'priority' => 'urgent',
        ])->assertUnprocessable();

        $this->assertSame('normal', $request->fresh()->priority->value);
    }

    public function test_manual_assignment_is_recorded_with_actor(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => null,
            'status' => 'new',
        ]);

        $this->withHeaders([
            'X-User-Id' => 31,
            'X-User-Role' => 'department_head',
            'X-Department-Id' => 3,
        ])->putJson("/api/requests/{$request->id}/assign", ['assigned_to' => 22])
            ->assertOk();

        $this->assertDatabaseHas('request_status_histories', [
            'request_id' => $request->id,
            'changed_by' => 31,
            'note' => 'Gán cán bộ xử lý #22.',
        ]);
    }

    public function test_department_head_cannot_update_or_assign_request_outside_their_department(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 6,
            'assigned_to' => null,
            'status' => 'new',
        ]);
        $headers = [
            'X-User-Id' => 31,
            'X-User-Role' => 'department_head',
            'X-Department-Id' => 3,
        ];

        $this->withHeaders($headers)->putJson("/api/requests/{$request->id}/status", ['status' => 'received'])
            ->assertForbidden();
        $this->withHeaders($headers)->putJson("/api/requests/{$request->id}/assign", ['assigned_to' => 22])
            ->assertForbidden();

        $this->assertNull($request->fresh()->assigned_to);
        $this->assertSame('new', $request->fresh()->status->value);
    }

    public function test_department_head_cannot_assign_request_outside_their_department_in_web(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 6,
            'assigned_to' => null,
            'status' => 'new',
        ]);

        $this->withSession(['fake_user' => [
            'id' => 31,
            'role' => 'department_head',
            'department_id' => 3,
            'full_name' => 'Trưởng phòng',
        ]])->put(route('requests.assign', $request), ['assigned_to' => 22])
            ->assertSessionHas('error');

        $this->assertNull($request->fresh()->assigned_to);
    }

    public function test_student_can_reply_to_info_request_and_return_ticket_to_staff(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'status' => 'waiting_info',
        ]);

        $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->postJson("/api/requests/{$request->id}/comments", [
            'body' => 'Em đã bổ sung thông tin theo yêu cầu.',
            'is_internal' => true,
        ])->assertCreated();

        $this->assertSame('in_progress', $request->fresh()->status->value);
        $this->assertDatabaseHas('ticket_comments', [
            'request_id' => $request->id,
            'user_role' => 'student',
            'is_internal' => false,
        ]);
    }

    public function test_student_reply_after_resolution_keeps_ticket_resolved_for_staff_to_close(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'status' => 'resolved',
            'resolved_at' => now()->subMinute(),
        ]);

        $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->postJson("/api/requests/{$request->id}/comments", ['body' => 'Em đã nhận được kết quả xử lý.'])
            ->assertCreated();

        $this->assertSame('resolved', $request->fresh()->status->value);
        $this->assertTrue($request->fresh()->hasStudentReplySinceResolution());
    }

    public function test_new_request_is_auto_assigned_to_least_loaded_staff_member(): void
    {
        SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => 21,
            'status' => 'in_progress',
        ]);

        $created = app(RequestWorkflowService::class)->create([
            'department_id' => 3,
            'support_type_id' => 5,
            'title' => 'Xin xác nhận thông tin học phí',
            'content' => 'Tôi cần hỗ trợ kiểm tra thông tin học phí trong học kỳ này.',
            'priority' => 'normal',
        ], 12);

        $this->assertSame(22, $created->assigned_to);
        $this->assertNotNull($created->assigned_at);
    }

    public function test_admin_can_transfer_request_and_request_is_assigned_in_target_department(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 3,
            'support_type_id' => 5,
            'assigned_to' => 21,
            'status' => 'in_progress',
        ]);

        $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Role' => 'admin',
        ])->putJson("/api/requests/{$request->id}/transfer", [
            'department_id' => 6,
            'support_type_id' => 8,
        ])->assertOk()
            ->assertJsonPath('data.department_id', 6)
            ->assertJsonPath('data.assigned_to', 22);

        $this->assertDatabaseHas('request_status_histories', [
            'request_id' => $request->id,
            'from_status' => 'in_progress',
            'to_status' => 'in_progress',
        ]);
    }

    public function test_admin_is_returned_to_list_after_transferring_request(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 3,
            'support_type_id' => 5,
            'status' => 'in_progress',
        ]);

        $this->withSession(['fake_user' => [
            'id' => 1,
            'role' => 'admin',
            'department_id' => null,
            'full_name' => 'Admin',
        ]])->put(route('requests.transfer', $request), [
            'department_id' => 6,
            'support_type_id' => 8,
        ])->assertRedirect(route('requests.index'))
            ->assertSessionHas('success');
    }

    public function test_department_head_cannot_transfer_request(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 3,
            'support_type_id' => 5,
            'status' => 'in_progress',
        ]);

        $this->withHeaders([
            'X-User-Id' => 31,
            'X-User-Role' => 'department_head',
            'X-Department-Id' => 3,
        ])->putJson("/api/requests/{$request->id}/transfer", [
            'department_id' => 6,
            'support_type_id' => 8,
        ])->assertForbidden();
    }

    public function test_duplicate_check_only_matches_same_student_and_open_requests(): void
    {
        $ownedRequest = SupportRequest::factory()->create([
            'student_id' => 12,
            'department_id' => 3,
            'title' => 'Xin xác nhận thông tin học phí',
            'status' => 'in_progress',
        ]);
        SupportRequest::factory()->create([
            'student_id' => 13,
            'department_id' => 3,
            'title' => 'Xin xác nhận thông tin học phí',
            'status' => 'in_progress',
        ]);
        SupportRequest::factory()->create([
            'student_id' => 12,
            'department_id' => 3,
            'title' => 'Xin xác nhận thông tin học phí',
            'status' => 'closed',
        ]);

        $matches = app(RequestWorkflowService::class)->findPotentialDuplicates([
            'department_id' => 3,
            'title' => 'Xin xác nhận thông tin học phí',
        ], 12);

        $this->assertSame([$ownedRequest->id], $matches->modelKeys());
    }

    public function test_student_can_reply_to_waiting_info_request_in_web(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'status' => 'waiting_info',
        ]);

        $this->withSession(['fake_user' => [
            'id' => 12,
            'role' => 'student',
            'full_name' => 'Sinh viên',
        ]])
            ->post(route('requests.comments.store', $request), ['body' => 'Em đã bổ sung giấy tờ theo yêu cầu.'])
            ->assertRedirect();

        $this->assertSame('in_progress', $request->fresh()->status->value);
        $this->assertDatabaseHas('ticket_comments', [
            'request_id' => $request->id,
            'user_id' => 12,
            'user_role' => 'student',
            'is_internal' => false,
        ]);
    }

    public function test_student_detail_shows_reply_and_rework_actions_without_close_action(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'status' => 'resolved',
            'resolved_at' => now()->subMinute(),
        ]);

        $this->withSession(['fake_user' => [
            'id' => 12,
            'role' => 'student',
            'full_name' => 'Sinh viên',
        ]])
            ->get(route('requests.show', $request))
            ->assertOk()
            ->assertSee('Trao đổi')
            ->assertSee('name="body"', false)
            ->assertSee('Yêu cầu xử lý lại')
            ->assertDontSee('name="status" value="closed"', false);
    }

    public function test_staff_close_action_appears_after_student_reply(): void
    {
        $request = SupportRequest::factory()->create([
            'assigned_to' => 21,
            'status' => 'resolved',
            'resolved_at' => now()->subMinute(),
        ]);
        $staffSession = ['fake_user' => [
            'id' => 21,
            'role' => 'staff',
            'department_id' => 3,
            'full_name' => 'Cán bộ',
        ]];

        $this->withSession($staffSession)->get(route('requests.show', $request))
            ->assertOk()
            ->assertSee('Chờ sinh viên phản hồi trước khi đóng')
            ->assertDontSee('name="status" value="closed"', false);

        TicketComment::create([
            'request_id' => $request->id,
            'user_id' => 12,
            'user_name' => 'Sinh viên',
            'user_role' => 'student',
            'body' => 'Em đã xem kết quả xử lý.',
            'is_internal' => false,
            'created_at' => now(),
        ]);

        $this->withSession($staffSession)->get(route('requests.show', $request))
            ->assertOk()
            ->assertSee('name="status" value="closed"', false);
    }

    public function test_student_can_confirm_creation_after_duplicate_warning(): void
    {
        SupportRequest::factory()->create([
            'student_id' => 12,
            'department_id' => 3,
            'title' => 'Xin xác nhận thông tin học phí',
            'status' => 'in_progress',
        ]);
        $data = [
            'department_id' => 3,
            'support_type_id' => 5,
            'title' => 'Xin xác nhận thông tin học phí',
            'content' => 'Tôi cần hỗ trợ kiểm tra thông tin học phí trong học kỳ này.',
            'priority' => 'normal',
        ];
        $session = ['fake_user' => ['id' => 12, 'role' => 'student']];

        $this->withSession($session)->post('/requests', $data)
            ->assertRedirect()
            ->assertSessionHas('possible_duplicates');
        $this->assertDatabaseCount('requests', 1);

        $this->withSession($session)->post('/requests', [...$data, 'confirm_duplicate' => '1'])
            ->assertRedirect();
        $this->assertDatabaseCount('requests', 2);
    }

    public function test_api_warns_about_duplicates_and_accepts_explicit_confirmation(): void
    {
        $existing = SupportRequest::factory()->create([
            'student_id' => 12,
            'department_id' => 3,
            'title' => 'Xin xác nhận thông tin học phí',
            'status' => 'in_progress',
        ]);
        $headers = [
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ];
        $payload = [
            'department_id' => 3,
            'support_type_id' => 5,
            'title' => 'Xin xác nhận thông tin học phí',
            'content' => 'Tôi cần hỗ trợ kiểm tra thông tin học phí trong học kỳ này.',
            'priority' => 'normal',
        ];

        $this->withHeaders($headers)->postJson('/api/requests', $payload)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, $existing->code));
        $this->assertDatabaseCount('requests', 1);

        $this->withHeaders($headers)->postJson('/api/requests', [...$payload, 'confirm_duplicate' => true])
            ->assertCreated();
        $this->assertDatabaseCount('requests', 2);
    }

    public function test_sla_warning_is_recorded_only_once(): void
    {
        SupportRequest::factory()->create([
            'assigned_to' => 21,
            'status' => 'in_progress',
            'created_at' => now()->subHours(8),
            'sla_deadline_at' => now()->addHours(2),
            'sla_flag' => 'on_time',
        ]);
        $sla = app(SlaService::class);

        $this->assertSame(1, $sla->checkAll()['warned']);
        $this->assertSame(0, $sla->checkAll()['warned']);
        $this->assertDatabaseCount('sla_notifications', 1);
    }

    public function test_sla_dry_run_reports_changes_without_mutating_data(): void
    {
        $request = SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => null,
            'status' => 'new',
            'created_at' => now()->subDays(3),
            'sla_deadline_at' => now()->subDay()->subMinute(),
            'sla_flag' => 'on_time',
        ]);

        $this->artisan('sla:check --dry')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertSame('on_time', $request->fresh()->sla_flag->value);
        $this->assertNull($request->fresh()->assigned_to);
        $this->assertDatabaseCount('sla_notifications', 0);
        $this->assertDatabaseCount('request_status_histories', 0);
    }

    public function test_sla_check_auto_assigns_unassigned_ticket_one_day_after_deadline(): void
    {
        SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => 21,
            'status' => 'in_progress',
        ]);
        $overdue = SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => null,
            'status' => 'new',
            'sla_deadline_at' => now()->subDay()->subMinute(),
            'sla_flag' => 'breached',
        ]);
        $notYetEligible = SupportRequest::factory()->create([
            'department_id' => 3,
            'assigned_to' => null,
            'status' => 'new',
            'sla_deadline_at' => now()->subDay()->addMinute(),
            'sla_flag' => 'breached',
        ]);

        $this->artisan('sla:check')->assertSuccessful();
        $this->artisan('sla:check')->assertSuccessful();

        $this->assertSame(22, $overdue->fresh()->assigned_to);
        $this->assertNull($notYetEligible->fresh()->assigned_to);
        $this->assertDatabaseHas('request_status_histories', [
            'request_id' => $overdue->id,
            'changed_by' => null,
            'note' => 'Tự động phân công cán bộ #22 do ticket quá hạn SLA hơn 24 giờ.',
        ]);
        $this->assertDatabaseCount('request_status_histories', 1);
    }

    public function test_student_cannot_see_internal_comments(): void
    {
        $request = SupportRequest::factory()->create(['student_id' => 12]);
        TicketComment::create([
            'request_id' => $request->id,
            'user_id' => 21,
            'user_name' => 'Staff',
            'user_role' => 'staff',
            'body' => 'Ghi chú chỉ dành cho cán bộ.',
            'is_internal' => true,
        ]);

        $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->getJson("/api/requests/{$request->id}/comments")
            ->assertOk()
            ->assertDontSee('Ghi chú chỉ dành cho cán bộ.');
    }

    public function test_student_can_open_copy_form_for_own_request(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'title' => 'Yêu cầu mẫu để sao chép',
        ]);

        $this->withSession(['fake_user' => ['id' => 12, 'role' => 'student']])
            ->get(route('requests.copy', $request))
            ->assertRedirect(route('requests.create', ['copy_from' => $request->id]));

        $this->withSession(['fake_user' => ['id' => 12, 'role' => 'student']])
            ->get(route('requests.create', ['copy_from' => $request->id]))
            ->assertOk()
            ->assertSee('Yêu cầu mẫu để sao chép');
    }

    public function test_create_form_renders_support_type_content_templates(): void
    {
        $response = $this->withSession(['fake_user' => ['id' => 12, 'role' => 'student']])
            ->get(route('requests.create'))
            ->assertOk()
            ->assertSee('data-template="Em cần giấy xác nhận đang là sinh viên', false)
            ->assertSee('placeholder="Chọn loại hỗ trợ để xem gợi ý nội dung..."', false)
            ->assertSee('-- Chọn phòng ban trước --');

        $this->assertMatchesRegularExpression(
            '/<select name="support_type_id"[^>]*\sdisabled(?:\s|>)/',
            $response->getContent(),
        );
    }

    public function test_support_type_select_is_enabled_when_department_is_already_selected(): void
    {
        $response = $this->withSession([
            'fake_user' => ['id' => 12, 'role' => 'student'],
            '_old_input' => ['department_id' => 3],
        ])->get(route('requests.create'))
            ->assertOk()
            ->assertSee('-- Chọn loại hỗ trợ --');

        $this->assertDoesNotMatchRegularExpression(
            '/<select name="support_type_id"[^>]*\sdisabled(?:\s|>)/',
            $response->getContent(),
        );
    }

    public function test_assigned_staff_name_is_displayed_instead_of_just_id(): void
    {
        $ticket = SupportRequest::factory()->create([
            'student_id' => 12,
            'assigned_to' => 21,
            'department_id' => 3,
        ]);

        // Kiểm tra Web Index hiển thị tên cán bộ thay vì chỉ hiện CB #21
        $this->withSession(['fake_user' => ['id' => 1, 'role' => 'admin', 'full_name' => 'Admin Hệ thống']])
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertDontSee('CB #21');

        // Kiểm tra Web Show hiển thị tên cán bộ
        $this->withSession(['fake_user' => ['id' => 1, 'role' => 'admin', 'full_name' => 'Admin Hệ thống']])
            ->get(route('requests.show', $ticket))
            ->assertOk()
            ->assertSee('Nguyễn Văn A');

        // Kiểm tra API trả về assigned_staff_name
        $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Role' => 'admin',
        ])->getJson("/api/requests/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.assigned_staff_name', 'Nguyễn Văn A');
    }

    public function test_requests_can_be_exported_to_excel_csv_with_utf8_bom(): void
    {
        $ticket = SupportRequest::factory()->create([
            'title' => 'Cần hỗ trợ giấy vay vốn ngân hàng',
            'student_id' => 12,
            'assigned_to' => 21,
            'department_id' => 3,
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        $response = $this->withSession(['fake_user' => ['id' => 1, 'role' => 'admin', 'full_name' => 'Admin Hệ thống']])
            ->get(route('requests.export', ['format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('danh-sach-yeu-cau-', $response->headers->get('Content-Disposition'));

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Kiểm tra UTF-8 BOM để Excel tiếng Việt không lỗi font
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('"Mã yêu cầu","Tiêu đề"', $content);
        $this->assertStringContainsString('"Cán bộ phụ trách"', $content);
        $this->assertStringContainsString($ticket->code, $content);
        $this->assertStringContainsString('Cần hỗ trợ giấy vay vốn ngân hàng', $content);
        $this->assertStringContainsString('Nguyễn Văn A', $content);
    }

    public function test_sla_statistics_are_computed_and_rendered_on_index(): void
    {
        SupportRequest::factory()->create([
            'status' => 'in_progress',
            'sla_flag' => 'warning',
            'sla_deadline_at' => now()->addHour(),
        ]);
        SupportRequest::factory()->create([
            'status' => 'closed',
            'sla_flag' => 'on_time',
            'rating' => 5,
        ]);

        $this->withSession(['fake_user' => ['id' => 1, 'role' => 'admin', 'full_name' => 'Admin Hệ thống']])
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Tỷ lệ đúng hạn SLA')
            ->assertSee('Sắp quá hạn')
            ->assertSee('Đánh giá CSAT')
            ->assertSee('Xuất Excel');
    }

    public function test_student_can_rate_closed_request_and_see_stars_and_comment(): void
    {
        $ticket = SupportRequest::factory()->create([
            'student_id' => 12,
            'status' => 'closed',
            'rating' => null,
            'rating_comment' => null,
        ]);

        $studentSession = ['fake_user' => ['id' => 12, 'role' => 'student', 'full_name' => 'Trần Thị B']];

        // Gửi đánh giá 5 sao
        $response = $this->withSession($studentSession)->post(route('requests.rating.store', $ticket), [
            'rating' => 5,
            'rating_comment' => 'Cán bộ xử lý rất nhanh chóng và chuyên nghiệp.',
        ]);

        $response->assertSessionHas('success', 'Cảm ơn bạn đã đánh giá kết quả hỗ trợ.');

        $fresh = $ticket->fresh();
        $this->assertSame(5, $fresh->rating);
        $this->assertSame('Cán bộ xử lý rất nhanh chóng và chuyên nghiệp.', $fresh->rating_comment);
        $this->assertNotNull($fresh->rated_at);

        // Xem lại trang show để kiểm tra sao và nhận xét
        $this->withSession($studentSession)->get(route('requests.show', $ticket))
            ->assertOk()
            ->assertSee('5 / 5 sao')
            ->assertSee('Cán bộ xử lý rất nhanh chóng và chuyên nghiệp.');
    }
}
