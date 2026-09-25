<?php

namespace App\Models;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\SlaFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bảng: requests
 *
 * Soft references (không FK thật sang Module 1/2):
 * student_id, department_id, support_type_id, assigned_to
 *
 * status/priority: string + Enum cast → thêm giá trị mới chỉ sửa Enum, không đụng DB ENUM.
 */
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
        'sla_deadline_at',
        'sla_flag',
    ];

    protected $casts = [
        'status'          => RequestStatus::class,
        'priority'        => RequestPriority::class,
        'sla_flag'        => SlaFlag::class,
        'assigned_at'     => 'datetime',
        'resolved_at'     => 'datetime',
        'closed_at'       => 'datetime',
        'sla_deadline_at' => 'datetime',
    ];

    public function statusHistories()
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id');
    }

    public function attachments()
    {
        return $this->hasMany(RequestAttachment::class, 'request_id');
    }

    public function slaNotifications()
    {
        return $this->hasMany(SlaNotification::class, 'request_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'request_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SLA Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Ticket có đang vi phạm SLA không?
     */
    public function isSlaBreached(): bool
    {
        return $this->sla_flag === SlaFlag::Breached;
    }

    /**
     * Ticket có đang sắp quá hạn không?
     */
    public function isSlaWarning(): bool
    {
        return $this->sla_flag === SlaFlag::Warning;
    }

    /**
     * Số giờ còn lại trước deadline. Trả về số âm nếu đã quá hạn.
     */
    public function slaRemainingHours(): ?float
    {
        if (! $this->sla_deadline_at) {
            return null;
        }

        return round(now()->diffInMinutes($this->sla_deadline_at, false) / 60, 1);
    }
}
