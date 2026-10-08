<?php

namespace Tests\Feature;

use App\Contracts\AuthContextInterface;
use App\Http\Middleware\AccountAuthentication;
use App\Services\Auth\FakeHeaderAuthContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.request_service.mock' => true, 'services.org_service.mock' => true]);
        $this->app->bind(AuthContextInterface::class, FakeHeaderAuthContext::class);
        $this->withoutMiddleware(AccountAuthentication::class);
    }

    public function test_staff_can_export_csv_report(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'staff',
            'X-Department-Id' => 3,
        ])->get('/api/reports/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }

    public function test_staff_can_export_pdf_report(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'staff',
            'X-Department-Id' => 3,
        ])->get('/api/reports/export-pdf');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_student_cannot_export_csv_report(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->getJson('/api/reports/export');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_export_pdf_report(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->getJson('/api/reports/export-pdf');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
