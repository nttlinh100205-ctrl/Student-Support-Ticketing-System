<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminChatTest extends TestCase
{
    public function test_widget_can_send_receive_and_poll_admin_reply(): void
    {
        $sent = $this->identify(12)->postJson('/api/support-chat', ['content' => 'Tin nhắn từ chatbox'])->assertCreated();
        $id = $sent->json('data.thread_id');
        $first = $this->getJson('/api/support-chat')->assertOk()->assertJsonPath('data.messages.0.content', 'Tin nhắn từ chatbox');
        $cursor = $first->json('data.messages.0.id');
        $this->identify(1, 'admin')->getJson('/api/support-chat')->assertOk()->assertJsonPath('data.threads.0.id', $id);
        $this->postJson('/api/support-chat', ['thread_id' => $id, 'content' => 'Phản hồi ngay trong khung chat'])->assertCreated();
        $this->identify(12)->getJson('/api/support-chat?after='.$cursor)->assertOk()->assertJsonCount(1, 'data.messages')->assertJsonPath('data.messages.0.content', 'Phản hồi ngay trong khung chat');
        $this->identify(13)->getJson('/api/support-chat?thread='.$id)->assertNotFound();
        $this->postJson('/api/support-chat', ['thread_id' => $id, 'content' => 'Không được gửi'])->assertForbidden();
    }

    public function test_widget_can_load_older_messages_without_exposing_other_threads(): void
    {
        $sent = $this->identify(12)->postJson('/api/support-chat', ['content' => 'Tin đầu tiên']);
        $id = $sent->json('data.thread_id');
        for ($i = 0; $i < 45; $i++) {
            DB::table('admin_chat_messages')->insert(['thread_id' => $id, 'sender_id' => 12, 'sender_name' => 'User 12', 'from_admin' => false, 'content' => 'Tin '.$i, 'created_at' => now()]);
        }
        $latest = $this->getJson('/api/support-chat')->assertOk()->assertJsonCount(40, 'data.messages');
        $this->getJson('/api/support-chat?before='.$latest->json('data.older_before'))->assertOk()->assertJsonCount(6, 'data.messages')->assertJsonPath('data.messages.0.content', 'Tin đầu tiên');
        $this->identify(13)->getJson('/api/support-chat')->assertOk()->assertJsonCount(0, 'data.messages')->assertJsonCount(0, 'data.threads');
    }

    private function identify(int $id, string $role = 'student'): static
    {
        return $this->withHeaders(['X-User-Id' => $id, 'X-User-Role' => $role, 'X-User-FullName' => 'User '.$id]);
    }

    public function test_user_sends_admin_replies_and_owner_reads_reply(): void
    {
        $this->identify(12)->post('/support-chat', ['content' => 'Cần hỗ trợ đăng nhập hệ thống'])->assertRedirect();
        $thread = DB::table('admin_chat_threads')->first();
        $this->assertTrue((bool) $thread->needs_reply);
        $this->identify(1, 'admin')->get('/support-chat')->assertOk()->assertSee('Cần hỗ trợ đăng nhập hệ thống');
        $this->post('/support-chat', ['thread_id' => $thread->id, 'content' => 'Admin đã nhận được thông tin.'])->assertRedirect();
        $this->assertDatabaseHas('admin_chat_threads', ['id' => $thread->id, 'needs_reply' => false]);
        $this->identify(12)->get('/support-chat')->assertOk()->assertSee('Admin đã nhận được thông tin.');
        $this->post('/support-chat', ['content' => 'Cảm ơn admin'])->assertRedirect();
        $this->assertDatabaseCount('admin_chat_threads', 1);
        $this->assertDatabaseCount('admin_chat_messages', 3);
        $this->assertDatabaseHas('admin_chat_threads', ['id' => $thread->id, 'needs_reply' => true]);
    }

    public function test_other_users_cannot_read_or_reply_to_private_conversation(): void
    {
        $this->identify(12)->post('/support-chat', ['content' => 'Nội dung riêng tư']);
        $id = DB::table('admin_chat_threads')->value('id');
        $this->identify(13)->get('/support-chat?thread='.$id)->assertNotFound();
        $this->post('/support-chat', ['thread_id' => $id, 'content' => 'Truy cập trái phép'])->assertForbidden();
        $this->identify(14, 'staff')->get('/support-chat?thread='.$id)->assertNotFound();
        $this->assertDatabaseCount('admin_chat_messages', 1);
    }

    public function test_invalid_messages_and_unselected_admin_thread_are_rejected(): void
    {
        $this->identify(12)->post('/support-chat', ['content' => '   '])->assertSessionHasErrors('content');
        $this->post('/support-chat', ['content' => str_repeat('x', 4001)])->assertSessionHasErrors('content');
        $this->identify(1, 'admin')->post('/support-chat', ['content' => 'Reply'])->assertStatus(422);
        $this->assertDatabaseCount('admin_chat_messages', 0);
    }

    public function test_message_markup_is_escaped_and_guests_are_redirected(): void
    {
        $this->identify(12)->post('/support-chat', ['content' => '<script>alert(1)</script>']);
        $this->get('/support-chat')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        config(['account.fake' => false]);
        $this->get('/support-chat')->assertRedirect(route('account.start'));
    }
}
