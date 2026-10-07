<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lưu lịch sử thông báo SLA đã gửi cho mỗi ticket.
 * Unique constraint (request_id, type) đảm bảo mỗi loại cảnh báo chỉ gửi 1 lần.
 */
class SlaNotification extends Model
{
    public $timestamps = false;

    protected $table = 'sla_notifications';

    protected $fillable = [
        'request_id',
        'type',
        'notified_user_id',
        'message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(SupportRequest::class, 'request_id');
    }
}
