@php
    $guidance = match ($statusVal) {
        'new' => $request->assigned_to ? 'Yêu cầu đã được gửi và phân công. Cán bộ phụ trách sẽ tiếp nhận để xử lý.' : 'Yêu cầu đang chờ trưởng phòng hoặc quản trị viên phân công cán bộ.',
        'received' => 'Cán bộ đã tiếp nhận yêu cầu. Bước tiếp theo là bắt đầu xử lý.',
        'in_progress' => 'Cán bộ đang xử lý. Theo dõi phần trao đổi và lịch sử để biết tiến độ mới nhất.',
        'waiting_info' => 'Sinh viên cần gửi thêm thông tin trong phần trao đổi. Sau khi gửi, yêu cầu tự chuyển về đang xử lý.',
        'resolved' => $hasStudentReply ? 'Sinh viên đã phản hồi kết quả. Cán bộ kiểm tra phản hồi trước khi đóng yêu cầu.' : 'Kết quả đã sẵn sàng. Sinh viên kiểm tra và xác nhận trong phần trao đổi, hoặc yêu cầu xử lý lại.',
        'closed' => $request->rating ? 'Yêu cầu đã hoàn tất và được đánh giá.' : 'Yêu cầu đã hoàn tất. Sinh viên có thể đánh giá chất lượng hỗ trợ bên dưới.',
        'rejected' => 'Yêu cầu đã bị từ chối. Xem lý do trong lịch sử xử lý để điều chỉnh hồ sơ hoặc liên hệ phòng ban.',
        default => 'Yêu cầu đã hủy. Sinh viên có thể sao chép nội dung để gửi một yêu cầu mới nếu cần.',
    };
@endphp
<section class="request-next-step" aria-label="Bước tiếp theo">
    <div><span class="request-eyebrow">TIẾN ĐỘ YÊU CẦU</span><h2>{{ $statusLabels[$statusVal] }}</h2><p>{{ $guidance }}</p></div>
    <div class="request-step-actions">
        @if($canStudentReply)
            <a href="#comment-body">{{ $statusVal === 'waiting_info' ? 'Bổ sung thông tin' : 'Phản hồi kết quả' }} ↓</a>
        @elseif($canRate)
            <a href="#request-rating-title">Đánh giá hỗ trợ ↓</a>
        @elseif($canAssign && !$request->assigned_to && !$isTerminal)
            <a href="#assignment-staff">Phân công cán bộ ↓</a>
        @elseif($canChangeStatus && count($allowedNext) && !$needsAssign && !$isTerminal)
            <a href="#workflow-note">Cập nhật xử lý ↓</a>
        @endif
        @if($isStudentOwner && $statusVal === 'resolved' && !$hasStudentReply)
            <form method="POST" action="{{ route('requests.comments.store', $request) }}">
                @csrf
                <input type="hidden" name="body" value="Tôi đã kiểm tra và xác nhận kết quả xử lý đáp ứng yêu cầu. Cảm ơn cán bộ hỗ trợ.">
                <button type="submit">Xác nhận kết quả phù hợp</button>
            </form>
        @endif
    </div>
</section>
