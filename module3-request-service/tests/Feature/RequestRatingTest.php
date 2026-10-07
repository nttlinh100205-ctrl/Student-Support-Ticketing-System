<?php

namespace Tests\Feature;

use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestRatingTest extends TestCase
{
    public function test_owner_can_rate_a_closed_request_once(): void
    {
        $request = SupportRequest::factory()->create([
            'student_id' => 12,
            'status' => 'closed',
        ]);
        $session = ['fake_user' => ['id' => 12, 'role' => 'student']];

        $this->withSession($session)
            ->post(route('requests.rating.store', $request), [
                'rating' => 5,
                'rating_comment' => 'Được hỗ trợ nhanh chóng.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'rating' => 5,
            'rating_comment' => 'Được hỗ trợ nhanh chóng.',
        ]);

        $this->withSession($session)
            ->post(route('requests.rating.store', $request), ['rating' => 1])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'rating' => 5]);
    }

    public function test_only_owner_can_rate_and_request_must_be_closed(): void
    {
        $openRequest = SupportRequest::factory()->create([
            'student_id' => 12,
            'status' => 'resolved',
        ]);
        $closedRequest = SupportRequest::factory()->create([
            'student_id' => 12,
            'status' => 'closed',
        ]);

        $this->withSession(['fake_user' => ['id' => 12, 'role' => 'student']])
            ->post(route('requests.rating.store', $openRequest), ['rating' => 4])
            ->assertSessionHas('error');

        $this->withSession(['fake_user' => ['id' => 99, 'role' => 'student']])
            ->post(route('requests.rating.store', $closedRequest), ['rating' => 4])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('requests', ['id' => $openRequest->id, 'rating' => null]);
        $this->assertDatabaseHas('requests', ['id' => $closedRequest->id, 'rating' => null]);
    }

    public function test_student_can_preview_public_attachment_but_not_internal_attachment(): void
    {
        Storage::fake('local');

        $request = SupportRequest::factory()->create(['student_id' => 12]);
        $publicComment = $this->createComment($request, false);
        $internalComment = $this->createComment($request, true);
        $publicAttachment = $this->createAttachment($publicComment, 'public.txt');
        $internalAttachment = $this->createAttachment($internalComment, 'internal.txt');
        $session = ['fake_user' => ['id' => 12, 'role' => 'student']];

        $this->withSession($session)
            ->get(route('requests.comments.attachments.preview', [$request, $publicComment, $publicAttachment]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8');

        $this->withSession($session)
            ->get(route('requests.comments.attachments.preview', [$request, $internalComment, $internalAttachment]))
            ->assertNotFound();
    }

    private function createComment(SupportRequest $request, bool $isInternal): TicketComment
    {
        return TicketComment::create([
            'request_id' => $request->id,
            'user_id' => $isInternal ? 21 : 12,
            'user_name' => $isInternal ? 'Staff' : 'Student',
            'user_role' => $isInternal ? 'staff' : 'student',
            'body' => 'Attachment test',
            'is_internal' => $isInternal,
        ]);
    }

    private function createAttachment(TicketComment $comment, string $name): CommentAttachment
    {
        $path = 'private/comments/'.$comment->request_id.'/'.$comment->id.'/'.$name;
        Storage::disk('local')->put($path, 'test file');

        return $comment->attachments()->create([
            'original_name' => $name,
            'path' => $path,
            'mime_type' => 'text/plain',
            'size' => 9,
        ]);
    }
}