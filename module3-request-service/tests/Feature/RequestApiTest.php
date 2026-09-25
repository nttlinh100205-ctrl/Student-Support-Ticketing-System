<?php

namespace Tests\Feature;

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
}
