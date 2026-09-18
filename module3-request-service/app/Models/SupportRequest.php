<?php

namespace App\Models;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'requests';

    protected $fillable = [
        'code',
        'student_id',
        'department_id',
        'support_type_id',
        'assigned_to',
        'title',
        'content',
        'priority',
        'status',
        'cancelled_reason',
        'assigned_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
        'priority' => RequestPriority::class,
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function statusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id');
    }
}
