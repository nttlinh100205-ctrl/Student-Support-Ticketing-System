<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Services\ImageStorage;
use App\Services\RequestWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryImagesTest extends TestCase
{
    private bool $failUpload = false;

    private function image(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['images.driver' => 'cloudinary', 'images.cloud_name' => 'test-cloud', 'images.api_key' => 'test-key', 'images.api_secret' => 'test-secret']);
        Storage::fake('public');
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake([
            'https://api.cloudinary.com/*/image/upload' => function ($request) {
                preg_match('~unisupport/[a-zA-Z0-9/_-]+~', $request->body(), $matches);

                return $this->failUpload ? Http::response(['error' => 'test-secret'], 401) : Http::response(['public_id' => $matches[0], 'format' => 'png']);
            },
            'https://api.cloudinary.com/*/image/destroy' => Http::response(['result' => 'ok']),
            'https://res.cloudinary.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/png']),
        ]);
    }

    public function test_request_image_is_uploaded_and_only_owner_can_view_it(): void
    {
        $ticket = SupportRequest::factory()->create(['student_id' => 12]);
        app(RequestWorkflowService::class)->storeAttachments($ticket, [$this->image('photo.png')]);
        $image = $ticket->attachments()->first();
        $this->assertStringStartsWith('cloudinary:', $image->path);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->withHeaders(['X-User-Id' => 12, 'X-User-Role' => 'student'])->get($image->url())->assertOk()->assertHeader('Content-Type', 'image/png')->assertSee('image-bytes');
        $this->withHeaders(['X-User-Id' => 13])->get($image->url())->assertForbidden();
        Http::assertSent(fn ($request) => $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/upload' && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-key:test-secret')));
    }

    public function test_chat_accepts_image_from_computer_and_hides_storage_reference(): void
    {
        $this->withHeaders(['X-User-Id' => 12, 'X-User-Role' => 'student'])->post('/api/support-chat', ['image' => $this->image('chat.png')], ['Accept' => 'application/json'])->assertCreated();
        $row = DB::table('admin_chat_messages')->first();
        $this->assertStringStartsWith('cloudinary:', $row->image_path);
        $this->getJson('/api/support-chat')->assertOk()->assertJsonMissingPath('data.messages.0.image_path')->assertJsonPath('data.messages.0.image_name', 'chat.png');
        $this->get('/api/support-chat/images/'.$row->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->withHeaders(['X-User-Id' => 13])->get('/api/support-chat/images/'.$row->id)->assertForbidden();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_failure_is_reported_without_local_fallback_or_secret(): void
    {
        $this->failUpload = true;
        $this->withHeaders(['X-User-Id' => 12, 'X-User-Role' => 'student'])->post('/api/support-chat', ['image' => $this->image('chat.png')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image')->assertDontSee('test-secret');
        $this->assertDatabaseCount('admin_chat_messages', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_delete_uses_cloudinary_and_documents_keep_existing_storage(): void
    {
        $images = app(ImageStorage::class);
        $path = $images->store($this->image('photo.png'), 'tests');
        $images->delete($path);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/image/destroy') && str_contains($request->body(), 'authenticated'));
        $document = $images->store(UploadedFile::fake()->create('document.pdf', 2, 'application/pdf'), 'documents');
        Storage::disk('local')->assertExists($document);
    }
}
