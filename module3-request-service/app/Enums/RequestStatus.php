<?php

namespace App\Enums;

/**
 * Trạng thái của 1 yêu cầu hỗ trợ (state machine).

 */
enum RequestStatus: string
{
    case New = 'new';
    case Received = 'received';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Mới tạo',
            self::Received => 'Đã tiếp nhận',
            self::InProgress => 'Đang xử lý',
            self::Resolved => 'Đã xử lý xong',
            self::Closed => 'Đã đóng',
            self::Cancelled => 'Đã hủy',
        };
    }
}
