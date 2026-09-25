<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_report_statistics(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'staff',
            'X-Department-Id' => 3,
        ])->getJson('/api/reports/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_requests',
                    'by_status' => [
                        'new',
                        'received',
                        'in_progress',
                        'resolved',
                        'closed',
                        'cancelled',
                    ],
                    'by_department',
                    'by_support_type',
                    'avg_processing_hours',
                    'requests_over_time',
                    'ratings_summary' => [
                        'total_ratings',
                        'average_rating',
                        'by_stars',
                        'by_department',
                    ],
                ],
                'message',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_department_head_can_filter_statistics_by_department(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 5,
            'X-User-Role' => 'department_head',
            'X-Department-Id' => 3,
        ])->getJson('/api/reports/statistics?department_id=3');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_statistics_can_filter_by_date_range(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Role' => 'admin',
        ])->getJson('/api/reports/statistics?from_date=2026-09-01&to_date=2026-09-03');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_student_cannot_view_statistics(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->getJson('/api/reports/statistics');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_request_is_forbidden(): void
    {
        $response = $this->getJson('/api/reports/statistics');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_date_range_fails_validation(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 1,
            'X-User-Role' => 'admin',
        ])->getJson('/api/reports/statistics?from_date=2026-09-10&to_date=2026-09-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_date']);
    }
}
