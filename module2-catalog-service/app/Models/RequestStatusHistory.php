<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bảng: request_status_histories.
 * Ghi lại lịch sử đổi trạng thái để Module 5 (Report) lấy dữ liệu thống kê
 * (GET /api/requests sang Module 3 — Mục 7 tài liệu).
 */
class RequestStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'request_status_histories';

    protected $fillable = [
        'request_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    protected $dates = ['created_at'];

    public function request()
    {
        return $this->belongsTo(SupportRequest::class, 'request_id');
    }
}
