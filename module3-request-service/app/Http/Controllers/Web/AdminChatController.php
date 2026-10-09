<?php

namespace App\Http\Controllers\Web;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminChatController extends Controller
{
    public function index(Request $request, AuthContext $auth)
    {
        $isAdmin = $auth->role() === 'admin';
        $threads = $isAdmin ? DB::table('admin_chat_threads')->orderByDesc('needs_reply')->orderByDesc('updated_at')->paginate(20, ['*'], 'inbox_page')->withQueryString() : null;
        $selected = $request->integer('thread');
        $query = DB::table('admin_chat_threads');
        if (! $isAdmin) {
            $query->where('user_id', $auth->userId());
        }
        $thread = $selected ? $query->where('id', $selected)->first() : ($isAdmin ? $threads->first() : $query->first());
        abort_if($selected && ! $thread, 404);
        $messages = $thread ? DB::table('admin_chat_messages')->where('thread_id', $thread->id)->orderByDesc('id')->paginate(40)->withQueryString() : null;

        return view('support-chat.index', compact('threads', 'thread', 'messages', 'isAdmin'));
    }

    public function store(Request $request, AuthContext $auth)
    {
        $request->merge(['content' => trim((string) $request->input('content'))]);
        $data = $request->validate(['content' => 'required|string|max:4000', 'thread_id' => 'nullable|integer|min:1']);
        $isAdmin = $auth->role() === 'admin';
        abort_if($isAdmin && empty($data['thread_id']), 422, 'Chọn cuộc trò chuyện cần trả lời.');
        $id = DB::transaction(function () use ($data, $isAdmin, $auth) {
            if (! $isAdmin && empty($data['thread_id'])) {
                DB::table('admin_chat_threads')->insertOrIgnore([
                    'user_id' => $auth->userId(), 'user_name' => $auth->fullName() ?: 'Người dùng #'.$auth->userId(),
                    'needs_reply' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $query = DB::table('admin_chat_threads');
            if (! empty($data['thread_id'])) {
                $query->where('id', $data['thread_id']);
            } else {
                $query->where('user_id', $auth->userId());
            }
            $thread = $query->lockForUpdate()->first();
            abort_unless($thread && ($isAdmin || $thread->user_id === $auth->userId()), 403);
            DB::table('admin_chat_messages')->insert([
                'thread_id' => $thread->id, 'sender_id' => $auth->userId(),
                'sender_name' => $auth->fullName() ?: ($isAdmin ? 'Quản trị viên' : 'Người dùng'),
                'from_admin' => $isAdmin, 'content' => $data['content'], 'created_at' => now(),
            ]);
            DB::table('admin_chat_threads')->where('id', $thread->id)->update(['needs_reply' => ! $isAdmin, 'updated_at' => now()]);

            return $thread->id;
        });

        return redirect()->route('support-chat.index', ['thread' => $id])->with('success', 'Tin nhắn đã được gửi.');
    }
}
