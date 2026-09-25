<?php

namespace Tests\Feature;

use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_rate_resolved_request(): void
    {
        // Request 101 belongs to student_id: 12 and is 'resolved' in mock_requests.json
        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->postJson('/api/ratings', [
            'request_id' => 101,
            'rating' => 5,
            'comment' => 'Rất hài lòng với dịch vụ hỗ trợ.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.request_id', 101)
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('ratings', [
            'request_id' => 101,
            'student_id' => 12,
            'rating' => 5,
        ]);
    }

    public function test_student_cannot_rate_other_students_request(): void
    {
        // Request 101 belongs to student_id: 12. Student 99 tries to rate it.
        $response = $this->withHeaders([
            'X-User-Id' => 99,
            'X-User-Role' => 'student',
        ])->postJson('/api/ratings', [
            'request_id' => 101,
            'rating' => 4,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_rate_unresolved_request(): void
    {
        // Request 102 belongs to student_id: 13 but status is 'in_progress'
        $response = $this->withHeaders([
            'X-User-Id' => 13,
            'X-User-Role' => 'student',
        ])->postJson('/api/ratings', [
            'request_id' => 102,
            'rating' => 4,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_rate_same_request_twice(): void
    {
        // Request 101
        Rating::factory()->create([
            'request_id' => 101,
            'student_id' => 12,
            'rating' => 4,
        ]);

        $response = $this->withHeaders([
            'X-User-Id' => 12,
            'X-User-Role' => 'student',
        ])->postJson('/api/ratings', [
            'request_id' => 101,
            'rating' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_staff_cannot_submit_student_rating(): void
    {
        $response = $this->withHeaders([
            'X-User-Id' => 2,
            'X-User-Role' => 'staff',
        ])->postJson('/api/ratings', [
            'request_id' => 101,
            'rating' => 5,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_staff_can_view_ratings_list_and_summary(): void
    {
        Rating::factory()->create([
            'request_id' => 101,
            'student_id' => 12,
            'department_id' => 3,
            'rating' => 5,
        ]);

        $listResponse = $this->withHeaders([
            'X-User-Id' => 2,
            'X-User-Role' => 'staff',
            'X-Department-Id' => 3,
        ])->getJson('/api/ratings');

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $summaryResponse = $this->withHeaders([
            'X-User-Id' => 2,
            'X-User-Role' => 'staff',
            'X-Department-Id' => 3,
        ])->getJson('/api/reports/ratings/statistics');

        $summaryResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_ratings',
                    'average_rating',
                    'by_stars',
                    'by_department',
                ],
            ]);
    }
}
