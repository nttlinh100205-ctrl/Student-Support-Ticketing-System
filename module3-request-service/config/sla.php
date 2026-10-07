<?php

/**
 * Cấu hình SLA (Service Level Agreement) cho hệ thống ticketing.
 *
 * - deadline_hours: thời hạn xử lý (giờ) tính từ lúc tạo ticket.
 * - warning_threshold_percent: khi đã trôi qua X% thời gian → chuyển cờ "warning".
 * - check_interval_minutes: tần suất chạy cron kiểm tra (phút).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Thời hạn SLA theo Priority (giờ)
    |--------------------------------------------------------------------------
    | Mỗi priority có một mốc deadline riêng.
    | Khi ticket được tạo, sla_deadline_at = created_at + deadline_hours.
    */
    'deadline_hours' => [
        'urgent' => 4,      // 4 giờ
        'high'   => 8,      // 8 giờ
        'normal' => 24,     // 24 giờ (1 ngày)
        'low'    => 72,     // 72 giờ (3 ngày)
    ],

    /*
    |--------------------------------------------------------------------------
    | Ngưỡng cảnh báo sớm (Warning Threshold)
    |--------------------------------------------------------------------------
    | Khi đã trôi qua >= X% thời gian deadline mà ticket chưa resolved/closed
    | → chuyển cờ từ "on_time" sang "warning".
    | Ví dụ: 75 = cảnh báo khi đã dùng hết 75% thời gian.
    */
    'warning_threshold_percent' => 75,

    /*
    |--------------------------------------------------------------------------
    | Tần suất chạy Cron (phút)
    |--------------------------------------------------------------------------
    | Job kiểm tra SLA sẽ chạy mỗi X phút.
    | Giá trị nhỏ = phát hiện nhanh hơn nhưng tốn tài nguyên hơn.
    */
    'check_interval_minutes' => 5,

    /*
    |--------------------------------------------------------------------------
    | Trạng thái không cần kiểm tra SLA
    |--------------------------------------------------------------------------
    | Ticket ở các trạng thái này sẽ bị bỏ qua khi quét SLA.
    */
    'excluded_statuses' => [
        'resolved',
        'closed',
        'cancelled',
    ],
];
