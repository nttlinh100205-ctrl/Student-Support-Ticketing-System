<?php
namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryNewsTest extends TestCase
{
    private bool $failUpload = false;

    protected function setUp(): void
    {
        parent::setUp();
        config(['account.url' => 'http://accounts.test', 'images.driver' => 'cloudinary', 'images.cloud_name' => 'test-cloud', 'images.api_key' => 'test-key', 'images.api_secret' => 'test-secret']);
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'http://accounts.test/api/v1/auth/me' => Http::response(['user' => ['id' => 72, 'name' => 'Admin', 'role' => 'ADMIN', 'status' => 'ACTIVE']]),
            'https://api.cloudinary.com/*/image/upload' => function ($request) {
                preg_match('~unisupport/[a-zA-Z0-9/_-]+~', $request->body(), $matches);
                return $this->failUpload ? Http::response(['error' => 'secret'], 401) : Http::response(['public_id' => $matches[0], 'format' => 'png']);
            },
            'https://res.cloudinary.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/png']),
            'https://api.cloudinary.com/*/image/destroy' => Http::response(['result' => 'ok']),
        ]);
        $this->withToken('token');
    }

    private function payload(): array
    {
        return ['title' => 'Thông báo', 'content' => 'Nội dung', 'category' => 'dao_tao', 'file' => UploadedFile::fake()->createWithContent('news.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='))];
    }

    public function test_news_image_is_uploaded_viewed_and_deleted_through_cloudinary(): void
    {
        $this->post('/api/news', $this->payload(), ['Accept' => 'application/json'])->assertCreated();
        $saved = json_decode(Storage::disk('local')->get('mock_data/news.json'), true);
        $this->assertStringStartsWith('cloudinary:', $saved[0]['file_path']);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->get('/api/news/1/file')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->deleteJson('/api/news/1')->assertOk();
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/image/destroy'));
    }

    public function test_failed_replacement_preserves_the_old_image(): void
    {
        $old = ['id' => 1, 'owner_id' => 72, 'title' => 'Old', 'file_path' => 'news/old.png'];
        Storage::disk('local')->put('mock_data/news.json', json_encode([$old]));
        Storage::disk('public')->put('news/old.png', 'old-image');
        $this->failUpload = true;
        $this->put('/api/news/1', $this->payload(), ['Accept' => 'application/json'])->assertUnprocessable();
        Storage::disk('public')->assertExists('news/old.png');
        $this->assertSame([$old], json_decode(Storage::disk('local')->get('mock_data/news.json'), true));
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/image/destroy'));
    }
}
