<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    private string $key = 'operation-test-00000001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-User-Id' => 12, 'X-User-Role' => 'student']);
    }

    private function ticket(): array
    {
        return ['department_id' => 1, 'support_type_id' => 1, 'title' => 'Xin giấy xác nhận sinh viên', 'content' => 'Em cần giấy xác nhận sinh viên để hoàn thiện hồ sơ.', '_idempotency_key' => $this->key];
    }

    public function test_api_retry_returns_original_ticket_and_one_history(): void
    {
        $first = $this->postJson('/api/requests', $this->ticket())->assertCreated();
        $second = $this->postJson('/api/requests', $this->ticket())->assertCreated()->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($first->json(), $second->json());
        $this->assertDatabaseCount('requests', 1);
        $this->assertDatabaseCount('request_status_histories', 1);
    }

    public function test_web_resubmission_redirects_to_same_ticket(): void
    {
        $first = $this->post('/requests', $this->ticket())->assertRedirect();
        $this->post('/requests', $this->ticket())->assertRedirect($first->headers->get('Location'))->assertHeader('Idempotency-Replayed', 'true');
        $this->assertDatabaseCount('requests', 1);
    }

    public function test_chat_retry_is_deduplicated_but_new_operation_can_repeat_text(): void
    {
        $body = ['content' => 'Xin hỗ trợ', '_idempotency_key' => $this->key];
        $this->postJson('/api/support-chat', $body)->assertCreated();
        $this->postJson('/api/support-chat', $body)->assertCreated()->assertHeader('Idempotency-Replayed', 'true');
        $this->assertDatabaseCount('admin_chat_messages', 1);
        $body['_idempotency_key'] = 'operation-test-00000002';
        $this->postJson('/api/support-chat', $body)->assertCreated();
        $this->assertDatabaseCount('admin_chat_messages', 2);
    }

    public function test_changed_payload_is_rejected_and_users_have_independent_keys(): void
    {
        $this->postJson('/api/support-chat', ['content' => 'A'], ['Idempotency-Key' => $this->key])->assertCreated();
        $this->postJson('/api/support-chat', ['content' => 'B'], ['Idempotency-Key' => $this->key])->assertConflict();
        $this->withHeaders(['X-User-Id' => 13])->postJson('/api/support-chat', ['content' => 'B'], ['Idempotency-Key' => $this->key])->assertCreated();
        $this->assertDatabaseCount('admin_chat_messages', 2);
        $this->assertDatabaseCount('admin_chat_threads', 2);
    }

    public function test_validation_failure_does_not_consume_key(): void
    {
        $this->postJson('/api/support-chat', ['_idempotency_key' => $this->key])->assertUnprocessable();
        $this->assertDatabaseCount('idempotent_operations', 0);
        $this->postJson('/api/support-chat', ['content' => 'Đã sửa nội dung', '_idempotency_key' => $this->key])->assertCreated();
    }

    public function test_image_retry_stores_one_attachment(): void
    {
        Storage::fake('local');
        $image = fn () => UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->post('/api/support-chat', ['image' => $image(), '_idempotency_key' => $this->key], ['Accept' => 'application/json'])->assertCreated();
        $this->post('/api/support-chat', ['image' => $image(), '_idempotency_key' => $this->key], ['Accept' => 'application/json'])->assertCreated()->assertHeader('Idempotency-Replayed', 'true');
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('admin_chat_messages', 1);
    }
}
