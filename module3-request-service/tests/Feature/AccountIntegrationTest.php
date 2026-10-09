<?php

namespace Tests\Feature;

use App\Contracts\AuthContext;
use App\Services\Auth\AccountAuthContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccountIntegrationTest extends TestCase
{
    public function test_repeated_reads_cache_catalog_but_revalidate_identity(): void
    {
        $this->fakeAccount();
        $this->withToken('token')->getJson('/api/integration-identity')->assertOk();
        $this->getJson('/api/integration-identity')->assertOk();
        Http::assertSentCount(5); // 2 kiểm tra tài khoản + 3 danh mục.
        $this->travel(31)->seconds();
        $this->getJson('/api/integration-identity')->assertOk();
        Http::assertSentCount(9);
    }

    public function test_writes_refresh_catalog_and_invalidate_cached_read(): void
    {
        $this->fakeAccount();
        Route::middleware('account.auth')->post('/api/integration-write', fn () => ['ok' => true]);
        $this->withToken('token')->getJson('/api/integration-identity')->assertOk();
        $this->postJson('/api/integration-write')->assertOk();
        Http::assertSentCount(8);
        $this->getJson('/api/integration-identity')->assertOk();
        Http::assertSentCount(12);
    }

    public function test_catalog_cache_is_isolated_between_tokens(): void
    {
        $this->fakeAccount();
        $this->withToken('first')->getJson('/api/integration-identity')->assertOk();
        $this->withToken('second')->getJson('/api/integration-identity')->assertOk();
        Http::assertSentCount(8);
    }

    public function test_cached_catalog_does_not_allow_a_revoked_session(): void
    {
        Http::fake([
            'http://accounts.test/api/v1/auth/me' => Http::sequence()
                ->push(['user' => ['id' => 42, 'name' => 'Sinh viên', 'role' => 'STUDENT', 'status' => 'ACTIVE']])
                ->push([], 401),
            'http://accounts.test/api/v1/directory/staff*' => Http::response(['data' => []]),
            'http://catalog.test/*' => Http::response(['data' => []]),
        ]);
        $this->withToken('token')->getJson('/api/integration-identity')->assertOk();
        $this->getJson('/api/integration-identity')->assertUnauthorized();
        Http::assertSentCount(5);
    }

    public function test_login_preserves_the_selected_request_queue_and_filters(): void
    {
        $this->get('/requests?queue=unassigned&sort=deadline')
            ->assertRedirect(route('account.start'))
            ->assertSessionHas('account_intended', '/requests?queue=unassigned&sort=deadline');
        Http::assertNothingSent();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['account.fake' => false, 'account.url' => 'http://accounts.test', 'account.catalog_url' => 'http://catalog.test']);
        $this->app->bind(AuthContext::class, AccountAuthContext::class);
        Http::preventStrayRequests();
        Route::middleware('account.auth')->get('/api/integration-identity', function (AuthContext $auth) {
            return ['id' => $auth->userId(), 'role' => $auth->role(), 'department_id' => $auth->departmentId(), 'name' => $auth->fullName()];
        });
    }

    private function fakeAccount(array $overrides = [], int $status = 200): void
    {
        Http::fake([
            'http://accounts.test/api/v1/auth/me' => Http::response(['user' => array_merge([
                'id' => 42, 'name' => 'Cán bộ thật', 'email' => 'staff@example.test', 'role' => 'STAFF', 'status' => 'ACTIVE', 'department_id' => 7,
            ], $overrides)], $status),
            'http://accounts.test/api/v1/directory/staff*' => Http::response(['data' => []]),
            'http://catalog.test/*' => Http::response(['data' => ['data' => [], 'last_page' => 1]]),
        ]);
    }

    public function test_bearer_identity_overrides_forged_admin_headers(): void
    {
        $this->fakeAccount();
        $this->withHeaders(['Authorization' => 'Bearer real-token', 'X-User-Id' => 1, 'X-User-Role' => 'admin'])
            ->getJson('/api/integration-identity')->assertOk()
            ->assertExactJson(['id' => 42, 'role' => 'staff', 'department_id' => 7, 'name' => 'Cán bộ thật']);
        Http::assertSent(fn ($request) => $request->url() === 'http://accounts.test/api/v1/auth/me' && $request->hasHeader('Authorization', 'Bearer real-token'));
    }

    public function test_fake_headers_alone_cannot_authenticate(): void
    {
        Http::fake();
        $this->withHeaders(['X-User-Id' => 1, 'X-User-Role' => 'admin'])->getJson('/api/integration-identity')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_locked_account_cannot_use_an_existing_token(): void
    {
        $this->fakeAccount(['status' => 'LOCKED']);
        $this->withToken('old-token')->getJson('/api/integration-identity')->assertForbidden();
        Http::assertSentCount(1);
    }

    public function test_department_head_without_department_is_rejected(): void
    {
        $this->fakeAccount(['role' => 'DEPARTMENT_HEAD', 'department_id' => null]);
        $this->withToken('token')->getJson('/api/integration-identity')->assertForbidden();
        Http::assertSentCount(1);
    }

    public function test_revoked_token_returns_401(): void
    {
        $this->fakeAccount([], 401);
        $this->withToken('revoked')->getJson('/api/integration-identity')->assertUnauthorized();
        Http::assertSentCount(1);
    }

    public function test_account_outage_does_not_fall_back_to_fake_user(): void
    {
        Http::fake(['http://accounts.test/*' => Http::failedConnection()]);
        $this->withToken('token')->getJson('/api/integration-identity')->assertStatus(503);
    }

    public function test_web_redirects_to_central_sign_in_and_rejects_invalid_state(): void
    {
        Http::fake();
        $this->get('/requests')->assertRedirect(route('account.start'));
        $response = $this->get('/auth/start');
        $response->assertRedirect();
        $this->assertStringStartsWith('http://accounts.test/sso/authorize?', $response->headers->get('Location'));
        $this->get('/auth/callback?state='.str_repeat('x', 64).'&code='.str_repeat('c', 64))->assertStatus(419);
        Http::assertNothingSent();
    }

    public function test_web_session_uses_server_verified_identity(): void
    {
        $this->fakeAccount(['role' => 'STUDENT', 'department_id' => null]);
        Route::middleware(['web', 'account.auth'])->get('/integration-web', fn () => request()->attributes->get('account_user'));
        $this->withSession(['account_token' => 'session-token', 'fake_user' => ['id' => 1, 'role' => 'admin']])
            ->get('/integration-web')->assertOk()->assertJsonPath('id', 42)->assertJsonPath('role', 'student');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer session-token'));
    }

    public function test_valid_callback_exchanges_code_and_stores_token_in_session(): void
    {
        $this->fakeAccount();
        Http::fake(['http://accounts.test/api/v1/auth/sso/exchange' => Http::response(['token' => 'shared-token'])]);
        $this->get('/auth/start')->assertRedirect();
        $login = session('account_login');
        $this->get('/auth/callback?state='.$login['state'].'&code='.str_repeat('c', 64))
            ->assertRedirect('/')->assertSessionHas('account_token', 'shared-token')->assertSessionMissing('account_login');
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['verifier'] === $login['verifier'] && $request['service'] === 'requests');
    }
}
