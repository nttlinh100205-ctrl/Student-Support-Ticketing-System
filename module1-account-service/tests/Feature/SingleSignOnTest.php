<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleSignOnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    }

    public function test_ticket_is_bound_to_service_and_verifier_and_can_only_be_used_once(): void
    {
        $user = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE']);
        $token = $user->createToken('test')->plainTextToken;
        $verifier = str_repeat('v', 64);
        $ticket = $this->withToken($token)->postJson('/api/v1/auth/sso/ticket', [
            'service' => 'requests', 'state' => str_repeat('s', 64), 'challenge' => hash('sha256', $verifier),
        ])->assertOk()->json('redirect');
        $this->assertStringStartsWith('http://localhost:8003/auth/callback?', $ticket);
        $this->assertStringNotContainsString($token, $ticket);
        parse_str(parse_url($ticket, PHP_URL_QUERY), $query);
        $body = ['service' => 'requests', 'code' => $query['code'], 'verifier' => $verifier];
        $this->postJson('/api/v1/auth/sso/exchange', array_merge($body, ['service' => 'news']))->assertUnauthorized();
        $this->postJson('/api/v1/auth/sso/exchange', array_merge($body, ['verifier' => str_repeat('x', 64)]))->assertUnauthorized();
        $this->postJson('/api/v1/auth/sso/exchange', $body)->assertOk()->assertJsonPath('token', $token);
        $this->postJson('/api/v1/auth/sso/exchange', $body)->assertUnauthorized();
    }

    public function test_identity_contains_department_and_rejects_locked_accounts(): void
    {
        $user = User::factory()->create(['role' => 'STAFF', 'status' => 'ACTIVE', 'department_id' => 83]);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.department_id', 83);
        $user->update(['status' => 'LOCKED']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_unknown_service_and_expired_code_cannot_be_exchanged(): void
    {
        $user = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE']);
        $token = $user->createToken('test')->plainTextToken;
        $body = ['service' => 'requests', 'state' => str_repeat('s', 64), 'challenge' => hash('sha256', str_repeat('v', 64))];
        $this->withToken($token)->postJson('/api/v1/auth/sso/ticket', array_merge($body, ['service' => 'https://attacker.test']))->assertUnprocessable();
        $redirect = $this->withToken($token)->postJson('/api/v1/auth/sso/ticket', $body)->assertOk()->json('redirect');
        parse_str(parse_url($redirect, PHP_URL_QUERY), $query);
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/sso/exchange', ['service' => 'requests', 'code' => $query['code'], 'verifier' => str_repeat('v', 64)])->assertUnauthorized();
    }
}
