<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['account.url' => 'http://accounts.test', 'app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        Storage::fake('local');
        Http::preventStrayRequests();
    }

    private function student(): void
    {
        Http::fake(['http://accounts.test/api/v1/auth/me' => Http::response(['user' => [
            'id' => 72, 'name' => 'Sinh viên thật', 'role' => 'STUDENT', 'status' => 'ACTIVE',
        ]])]);
    }

    public function test_news_requires_a_real_token(): void
    {
        $this->getJson('/api/news')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_root_login_return_path_is_a_single_slash(): void
    {
        $this->get('/')->assertRedirect(route('account.start'))
            ->assertSessionHas('account_intended', '/');
        Http::assertNothingSent();
    }

    public function test_author_is_taken_from_verified_identity_not_posted_owner(): void
    {
        $this->student();
        $this->withToken('token')->postJson('/api/news', [
            'title' => 'Thông báo', 'content' => 'Nội dung', 'category' => 'dao_tao',
            'owner_id' => 1, 'owner_name' => 'Admin giả', 'owner_role' => 'admin', 'is_pinned' => true,
        ])->assertCreated()->assertJsonPath('data.owner_id', 72)->assertJsonPath('data.owner_role', 'student');
        $saved = json_decode(Storage::disk('local')->get('mock_data/news.json'), true);
        $this->assertSame(72, $saved[0]['owner_id']);
        Http::assertSentCount(1);
    }

    public function test_student_cannot_delete_another_authors_news_with_forged_admin_role(): void
    {
        $this->student();
        Storage::disk('local')->put('mock_data/news.json', json_encode([['id' => 1, 'owner_id' => 99, 'title' => 'Protected']]));
        $this->withToken('token')->deleteJson('/api/news/1', ['owner_id' => 99, 'owner_role' => 'admin'])->assertForbidden();
        $this->assertCount(1, json_decode(Storage::disk('local')->get('mock_data/news.json'), true));
        Http::assertSentCount(1);
    }

    public function test_authenticated_web_renders_real_identity(): void
    {
        $this->student();
        $this->withSession(['account_token' => 'token'])->get('/support')->assertOk()->assertSee('Sinh viên thật');
        Http::assertSentCount(1);
    }
}
