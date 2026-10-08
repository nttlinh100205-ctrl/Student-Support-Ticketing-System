<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_rate_closed_ticket_once_and_api_exposes_result(): void
    {
        $ticket = SupportRequest::factory()->create(['student_id' => 12, 'status' => 'closed', 'rating' => null]);
        $this->withHeaders(['X-User-Id' => 12, 'X-User-Role' => 'student'])
            ->postJson('/api/requests/'.$ticket->id.'/rating', ['rating' => 5, 'comment' => 'Tốt'])
            ->assertCreated()->assertJsonPath('data.rating', 5);
        $this->assertDatabaseHas('requests', ['id' => $ticket->id, 'rating' => 5, 'rating_comment' => 'Tốt']);
        $this->postJson('/api/requests/'.$ticket->id.'/rating', ['rating' => 1])->assertStatus(409);
    }

    public function test_other_students_cannot_rate_a_ticket(): void
    {
        $ticket = SupportRequest::factory()->create(['student_id' => 12, 'status' => 'closed', 'rating' => null]);
        $this->withHeaders(['X-User-Id' => 13, 'X-User-Role' => 'student'])
            ->postJson('/api/requests/'.$ticket->id.'/rating', ['rating' => 5])->assertForbidden();
        $this->assertDatabaseHas('requests', ['id' => $ticket->id, 'rating' => null]);
    }
}
