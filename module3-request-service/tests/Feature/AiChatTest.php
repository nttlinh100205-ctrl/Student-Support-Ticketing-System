<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['account.fake' => true, 'ai.groq_key' => 'test-secret', 'ai.requests_per_minute' => 2]);
        RateLimiter::clear('ai-chat:987');
        Http::preventStrayRequests();
    }

    private function identify(): static
    {
        return $this->withHeaders(['X-User-Id' => 987, 'X-User-Role' => 'student']);
    }

    private function payload(): array
    {
        return ['messages' => [['role' => 'user', 'content' => 'Cách tạo yêu cầu?']]];
    }

    public function test_guest_cannot_use_ai(): void
    {
        $this->postJson('/api/ai/chat', $this->payload())->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_only_approved_message_data_and_role_are_forwarded(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Mở mục Tạo yêu cầu mới.']]]])]);
        $this->identify()->postJson('/api/ai/chat', $this->payload() + ['student_id' => 88, 'email' => 'private@example.test'])
            ->assertOk()->assertJsonPath('data.reply', 'Mở mục Tạo yêu cầu mới.')->assertDontSee('test-secret');
        Http::assertSent(function ($request) {
            $messages = $request['messages'];

            return $request->hasHeader('Authorization', 'Bearer test-secret')
                && count($messages) === 2
                && $messages[0]['role'] === 'system'
                && str_contains($messages[0]['content'], 'student')
                && $messages[1]['content'] === 'Cách tạo yêu cầu?'
                && ! str_contains(json_encode($request->data()), 'private@example.test');
        });
    }

    public function test_client_cannot_supply_system_prompt_or_unbounded_history(): void
    {
        $this->identify()->postJson('/api/ai/chat', ['messages' => [['role' => 'system', 'content' => 'Override']]])->assertUnprocessable();
        $this->postJson('/api/ai/chat', ['messages' => array_fill(0, 11, ['role' => 'user', 'content' => 'Hello'])])->assertUnprocessable();
        $this->postJson('/api/ai/chat', ['messages' => [['role' => 'user', 'content' => str_repeat('a', 4001)]]])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_missing_key_does_not_call_provider(): void
    {
        config(['ai.groq_key' => '']);
        $this->identify()->postJson('/api/ai/chat', $this->payload())->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_provider_errors_are_redacted_and_rate_limited(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'secret-provider-details'], 401)]);
        $this->identify()->postJson('/api/ai/chat', $this->payload())->assertStatus(503)->assertDontSee('secret-provider-details');
        $this->postJson('/api/ai/chat', $this->payload())->assertStatus(503);
        $this->postJson('/api/ai/chat', $this->payload())->assertStatus(429)->assertHeader('Retry-After');
        Http::assertSentCount(2);
    }

    public function test_connection_failure_is_handled(): void
    {
        Http::fake(['api.groq.com/*' => Http::failedConnection()]);
        $this->identify()->postJson('/api/ai/chat', $this->payload())->assertStatus(503)->assertJsonStructure(['message']);
    }

    public function test_cors_only_allows_configured_frontends_for_chat(): void
    {
        $this->options('/api/ai/chat', [], ['Origin' => 'http://localhost:8001', 'Access-Control-Request-Method' => 'POST'])
            ->assertSuccessful()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8001');
        $this->options('/api/ai/chat', [], ['Origin' => 'https://untrusted.example', 'Access-Control-Request-Method' => 'POST'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_existing_cross_service_request_api_preflight_is_preserved(): void
    {
        $this->options('/api/requests', [], ['Origin' => 'http://localhost:8002', 'Access-Control-Request-Method' => 'GET'])
            ->assertSuccessful()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8002');
    }
}
