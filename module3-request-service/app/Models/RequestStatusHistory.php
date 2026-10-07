<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/* 
 * Ghi lại lịch sử đổi trạng thái để Module 5 (Report) lấy dữ liệu thống kê

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
        'created_at',
    ];

    protected $dates = ['created_at'];

    public function request()
    {
        return $this->belongsTo(SupportRequest::class, 'request_id');
    }
}
