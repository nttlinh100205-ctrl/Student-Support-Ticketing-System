<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_student_cannot_export_csv_report(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->getJson('/api/reports/export');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
