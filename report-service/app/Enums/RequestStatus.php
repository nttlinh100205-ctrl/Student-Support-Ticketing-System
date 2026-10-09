<?php

namespace App\Enums;

enum RequestStatus: string
{
    case NEW = 'new';
    case RECEIVED = 'received';
    case IN_PROGRESS = 'in_progress';
    case WAITING_INFO = 'waiting_info';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Mới',
            self::RECEIVED => 'Đã tiếp nhận',
            self::IN_PROGRESS => 'Đang xử lý',
            self::WAITING_INFO => 'Chờ bổ sung',
            self::RESOLVED => 'Đã giải quyết',
            self::CLOSED => 'Đã đóng',
            self::CANCELLED => 'Đã hủy',
            self::REJECTED => 'Từ chối',
        };
    }
}
