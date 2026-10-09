<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveServiceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['account.url' => 'http://accounts.test', 'services.request_service.url' => 'http://requests.test',
            'services.org_service.url' => 'http://catalog.test', 'services.request_service.mock' => false, 'services.org_service.mock' => false]);
        Http::preventStrayRequests();
    }

    private function upstream(string $role = 'DEPARTMENT_HEAD', string $title = 'Yêu cầu hỗ trợ'): void
    {
        Http::fake([
            'http://accounts.test/api/v1/auth/me' => Http::response(['user' => ['id' => 7, 'name' => 'Trưởng phòng', 'role' => $role, 'status' => 'ACTIVE', 'department_id' => 1]]),
            'http://accounts.test/api/v1/directory/staff*' => Http::response(['data' => [['id' => 8, 'name' => 'Cán bộ A', 'role' => 'STAFF', 'department_id' => 1], ['id' => 9, 'name' => 'Cán bộ B', 'role' => 'STAFF', 'department_id' => 2]]]),
            'http://catalog.test/api/catalog/departments*' => Http::response(['data' => [['id' => 1, 'name' => 'Phòng A'], ['id' => 2, 'name' => 'Phòng B']]]),
            'http://catalog.test/api/catalog/support-types*' => Http::response(['data' => [['id' => 1, 'name' => 'Hỗ trợ A', 'department_id' => 1]]]),
            'http://requests.test/api/requests*' => Http::response(['data' => [['id' => 1, 'title' => $title, 'student_id' => 2, 'department_id' => 1, 'support_type_id' => 1, 'assigned_to' => 8, 'status' => 'new', 'created_at' => '2026-10-01T00:00:00Z', 'sla_deadline_at' => '2026-10-30T00:00:00Z', 'rating' => null]], 'meta' => ['last_page' => 1]]),
        ]);
    }

    public function test_department_scope_applies_to_rankings_and_filter_choices(): void
    {
        $this->upstream();
        $this->withToken('head')->getJson('/api/reports/statistics?department_id=2')
            ->assertOk()->assertJsonCount(1, 'data.staff_workloads')
            ->assertJsonPath('data.staff_workloads.0.staff_id', 8)
            ->assertJsonPath('data.staff_rankings.0.csat', null);
        $this->getJson('/api/reports/filters')->assertOk()
            ->assertJsonCount(1, 'data.departments')->assertJsonCount(1, 'data.staff');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'requests.test/api/requests') && (int) ($request->data()['department_id'] ?? 0) === 1);
    }

    public function test_student_cannot_open_report_page_or_export(): void
    {
        $this->upstream('STUDENT');
        $this->withToken('student')->get('/dashboard')->assertForbidden();
        $this->getJson('/api/reports/export')->assertForbidden();
        Http::assertSentCount(2);
    }

    public function test_report_renders_pdf_from_request_service_data(): void
    {
        $this->upstream();
        $response = $this->withToken('head')->get('/api/reports/export-pdf');
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_outage_does_not_replace_reports_with_mock_data(): void
    {
        Http::fake(['http://accounts.test/*' => Http::failedConnection()]);
        $this->withToken('head')->getJson('/api/reports/statistics')->assertStatus(503);
        Http::assertSentCount(1);
    }

    public function test_csv_keeps_student_text_as_text_in_excel(): void
    {
        $this->upstream('ADMIN', '=1+1');
        $response = $this->withToken('admin')->get('/api/reports/export');
        $response->assertOk();
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
    }
}
