@extends('layouts.app')
@section('title', $isAdmin ? 'Hộp thư hỗ trợ — UniSupport' : 'Liên hệ admin — UniSupport')
@section('content')
<link rel="stylesheet" href="/css/admin-chat.css">
<div class="admin-chat-page">
    <div class="chat-page-heading"><span>UNISUPPORT · HỖ TRỢ TRỰC TIẾP</span><h1>{{ $isAdmin ? 'Hộp thư hỗ trợ' : 'Trao đổi với quản trị viên' }}</h1><p>{{ $isAdmin ? 'Tiếp nhận và trả lời các cuộc trò chuyện của người dùng.' : 'Tin nhắn được gửi đến admin của hệ thống. Bạn có thể quay lại đây để xem phản hồi.' }}</p></div>
    <div class="admin-chat-grid {{ $isAdmin ? 'with-inbox' : '' }}">
    @if($isAdmin)
        <aside class="chat-inbox"><h2>Cuộc trò chuyện</h2>
            @forelse($threads as $item)
                <a href="{{ route('support-chat.index', ['thread' => $item->id]) }}" class="inbox-item {{ $thread?->id === $item->id ? 'selected' : '' }}"><strong>{{ $item->user_name }}</strong><small>#{{ $item->user_id }} · {{ $item->needs_reply ? 'Chờ trả lời' : 'Đã phản hồi' }}</small><span>{{ \Carbon\Carbon::parse($item->updated_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</span></a>
            @empty<p>Chưa có tin nhắn cần hỗ trợ.</p>@endforelse
            {{ $threads->links() }}
        </aside>
    @endif
        <section class="chat-thread"><header><h2>{{ $isAdmin ? ($thread?->user_name ?? 'Chọn cuộc trò chuyện') : 'Bộ phận quản trị hệ thống' }}</h2><p>Đây là trao đổi với người thật, tách biệt với tư vấn AI. Admin trả lời khi tiếp nhận được tin nhắn.</p><a href="{{ url()->full() }}">↻ Kiểm tra phản hồi mới</a></header>
            <div class="chat-transcript" aria-label="Lịch sử trao đổi">
                @if($messages && $messages->hasPages())<div class="chat-history-pages">{{ $messages->links() }}</div>@endif
                @forelse($messages ? $messages->getCollection()->reverse() : [] as $message)
                    <article class="chat-bubble {{ $message->from_admin ? 'from-admin' : 'from-user' }}"><strong>{{ $message->from_admin ? 'Admin · '.$message->sender_name : $message->sender_name }}</strong><p>{{ $message->content }}</p>@if($message->image_path)<img src="{{ route('support-chat.image', $message->id) }}" alt="{{ $message->image_name }}" style="max-width:100%;max-height:300px;border-radius:10px">@endif<time>{{ \Carbon\Carbon::parse($message->created_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</time></article>
                @empty<div class="chat-empty"><h3>Bắt đầu cuộc trò chuyện</h3><p>Mô tả vấn đề bạn gặp phải để admin hỗ trợ. Không gửi mật khẩu hoặc API key.</p></div>@endforelse
            </div>
            @if(!$isAdmin || $thread)
            <form class="chat-reply" method="POST" enctype="multipart/form-data" action="{{ route('support-chat.store') }}">@csrf
                @if($thread)<input type="hidden" name="thread_id" value="{{ $thread->id }}">@endif
                <label for="admin-chat-content">{{ $isAdmin ? 'Nội dung phản hồi' : 'Tin nhắn của bạn' }}</label>
                <textarea id="admin-chat-content" name="content" maxlength="4000" rows="3" placeholder="Nhập nội dung cần trao đổi…">{{ old('content') }}</textarea>
                <label for="admin-chat-image">Ảnh đính kèm</label><input id="admin-chat-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                @error('image')<p class="chat-error">{{ $message }}</p>@enderror
                @error('content')<p class="chat-error">{{ $message }}</p>@enderror
                <div><small>Tối đa 4.000 ký tự · Tin nhắn được lưu trong hệ thống</small><button type="submit">{{ $isAdmin ? 'Gửi phản hồi' : 'Gửi đến admin' }} →</button></div>
            </form>
            @endif
        </section>
    </div>
</div>
@endsection
