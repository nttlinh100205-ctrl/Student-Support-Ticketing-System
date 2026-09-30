<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\Jwt\JwtIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'avatar@example.com',
            'full_name' => 'Avatar User',
            'name' => 'Avatar User',
            'role' => 'student',
            'is_active' => true,
        ]);

        $token = app(JwtIssuer::class)->issue($user);

        $file = UploadedFile::fake()->image(
            'avatar.jpg',
            500,
            500
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->postJson('/api/auth/avatar', [
                'avatar' => $file,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Cập nhật ảnh đại diện thành công.'
            );

        $user->refresh();

        $this->assertNotNull($user->avatar_path);

        Storage::disk('public')->assertExists(
            $user->avatar_path
        );
    }

    public function test_avatar_upload_requires_jwt(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image(
            'avatar.jpg',
            500,
            500
        );

        $response = $this->postJson(
            '/api/auth/avatar',
            [
                'avatar' => $file,
            ]
        );

        $response
            ->assertStatus(401)
            ->assertJsonPath(
                'message',
                'Thiếu JWT Bearer token.'
            );
    }

    public function test_non_image_file_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'avatar-invalid@example.com',
            'full_name' => 'Invalid Avatar User',
            'name' => 'Invalid Avatar User',
            'role' => 'student',
            'is_active' => true,
        ]);

        $token = app(JwtIssuer::class)->issue($user);

        $file = UploadedFile::fake()->create(
            'avatar.txt',
            10,
            'text/plain'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->postJson('/api/auth/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_new_avatar_replaces_old_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'avatar-replace@example.com',
            'full_name' => 'Avatar Replace User',
            'name' => 'Avatar Replace User',
            'role' => 'student',
            'is_active' => true,
            'avatar_path' => 'avatars/old-avatar.jpg',
        ]);

        Storage::disk('public')->put(
            'avatars/old-avatar.jpg',
            'old avatar'
        );

        $token = app(JwtIssuer::class)->issue($user);

        $file = UploadedFile::fake()->image(
            'new-avatar.jpg',
            500,
            500
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->postJson('/api/auth/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk();

        $user->refresh();

        $this->assertNotSame(
            'avatars/old-avatar.jpg',
            $user->avatar_path
        );

        Storage::disk('public')->assertMissing(
            'avatars/old-avatar.jpg'
        );

        Storage::disk('public')->assertExists(
            $user->avatar_path
        );
    }
}