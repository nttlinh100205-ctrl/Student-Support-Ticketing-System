<?php

namespace Tests\Feature;

use App\Contracts\AuthContext;
use App\Models\SupportRequest;
use App\Services\Auth\AccountAuthContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestFormIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['account.fake' => false, 'account.url' => 'http://accounts.test', 'account.catalog_url' => 'http://catalog.test']);
        $this->app->bind(AuthContext::class, AccountAuthContext::class);
        Http::preventStrayRequests();
    }

    private function upstream(bool $valid = true): void
    {
        Http::fake([
            'http://accounts.test/api/v1/auth/me' => Http::response(['user' => ['id' => 12, 'name' => 'Sinh viên', 'email' => 'student@test.local', 'role' => 'STUDENT', 'status' => 'ACTIVE']]),
            'http://accounts.test/api/v1/directory/staff*' => Http::response(['data' => []]),
            'http://catalog.test/api/catalog/departments*' => Http::response(['data' => [['id' => 1, 'name' => 'Phòng hỗ trợ']]]),
            'http://catalog.test/api/catalog/support-types/1/form' => Http::response(['data' => ['fields' => [
                ['field_key' => 'class', 'label' => 'Lớp học', 'field_type' => 'text'],
                ['field_key' => 'proof', 'label' => 'Minh chứng', 'field_type' => 'file'],
            ]]]),
            'http://catalog.test/api/catalog/support-types/1/validate' => $valid
                ? Http::response(['data' => ['values' => ['class' => 'DH12', 'proof' => 'proof.pdf']]])
                : Http::response(['errors' => ['values.class' => ['Vui lòng nhập Lớp học.']]], 422),
            'http://catalog.test/api/catalog/support-types*' => Http::response(['data' => [['id' => 1, 'name' => 'Xác nhận', 'department_id' => 1]]]),
        ]);
    }

    public function test_loading_form_only_calls_identity_and_requested_definition(): void
    {
        $this->upstream();
        $this->withToken('student')->getJson('/request-forms/1')->assertOk()->assertJsonPath('data.fields.0.field_key', 'class');
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/directory/staff') || str_contains($request->url(), '/departments'));
    }

    private function payload(): array
    {
        return ['department_id' => 1, 'support_type_id' => 1, 'title' => 'Xin xác nhận sinh viên', 'content' => 'Em cần giấy xác nhận sinh viên để hoàn thành hồ sơ.', 'form_values' => ['class' => 'DH12']];
    }

    public function test_student_submits_catalog_fields_and_private_document(): void
    {
        Storage::fake('local');
        $this->upstream();
        $response = $this->withToken('student')->post('/api/requests', $this->payload() + ['form_files' => ['proof' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('data.form_data.0.value', 'DH12')->assertJsonMissingPath('data.form_data.1.path');
        $ticket = SupportRequest::findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($ticket->form_data[1]['path']);
        $this->get('/requests/'.$ticket->id.'/form-files/proof')->assertOk()->assertDownload('proof.pdf');
        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_ends_with($request->url(), '/validate') && $request['values']['proof'] === 'proof.pdf');
    }

    public function test_catalog_validation_stops_creation_and_keeps_field_error(): void
    {
        $this->upstream(false);
        $this->withToken('student')->postJson('/api/requests', $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('form_values.class');
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_student_cannot_download_another_students_form_file(): void
    {
        $this->upstream();
        $ticket = SupportRequest::factory()->create(['student_id' => 99, 'form_data' => [['key' => 'proof', 'path' => 'private.pdf', 'value' => 'proof.pdf']]]);
        $this->withToken('student')->get('/requests/'.$ticket->id.'/form-files/proof')->assertForbidden();
    }
}
