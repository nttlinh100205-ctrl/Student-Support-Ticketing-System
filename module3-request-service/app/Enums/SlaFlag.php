<?php

namespace App\Enums;

/**
 * Cờ SLA gắn trên mỗi ticket.
 *
 *  on_time  — còn trong thời hạn, chưa đến ngưỡng cảnh báo.
 *  warning  — sắp hết hạn (đã qua ngưỡng warning_threshold_percent).
 *  breached — đã quá hạn SLA.
 */
enum SlaFlag: string
{
    case OnTime   = 'on_time';
    case Warning  = 'warning';
    case Breached = 'breached';

    public function label(): string
    {
        return match ($this) {
            self::OnTime   => 'Đúng hạn',
            self::Warning  => 'Sắp quá hạn',
            self::Breached => 'Đã quá hạn',
        };
    }

    /**
     * Badge CSS class (dùng trên UI).
     */
    public function badge(): string
    {
        return match ($this) {
            self::OnTime   => 'badge-success',
            self::Warning  => 'badge-warning',
            self::Breached => 'badge-danger',
        };
    }
}
