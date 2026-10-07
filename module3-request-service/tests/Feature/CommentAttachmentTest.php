<?php

namespace Tests\Feature;

use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentAttachmentTest extends TestCase
{
    public function test_student_can_upload_attachment_to_comment_in_private_local_disk(): void
    {
        Storage::disk('local')->deleteDirectory('private/comments');

        $request = SupportRequest::create([
            'code' => 'YC-2026-0001',
            'student_id' => 101,
            'department_id' => 3,
            'support_type_id' => 1,
            'assigned_to' => null,
            'title' => 'Test ticket',
            'content' => 'Test content',
            'priority' => 'normal',
            'status' => 'new',
            'cancelled_reason' => null,
        ]);

        $comment = TicketComment::create([
            'request_id' => $request->id,
            'user_id' => 101,
            'user_name' => 'Student A',
            'user_role' => 'student',
            'body' => 'Need help',
            'is_internal' => false,
        ]);

        $this->assertSame($request->id, $comment->request_id);

        $file = UploadedFile::fake()->create('report.pdf', 120, 'application/pdf');

        $response = $this->withHeaders([
            'X-User-Id' => 101,
            'X-User-Role' => 'student',
        ])->postJson('/api/requests/'.$request->id.'/comments/'.$comment->id.'/attachments', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.original_name', 'report.pdf');

        $attachment = CommentAttachment::first();
        $this->assertNotNull($attachment);
        $this->assertStringStartsWith('private/comments/', $attachment->path);
        $this->assertTrue(Storage::disk('local')->exists($attachment->path));

        $preview = $this->get('/api/requests/'.$request->id.'/comments/'.$comment->id.'/attachments/'.$attachment->id.'/preview');

        $preview->assertOk();
    }
}
