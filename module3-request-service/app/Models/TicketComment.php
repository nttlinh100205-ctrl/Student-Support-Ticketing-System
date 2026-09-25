<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bình luận (comment) trong thread trao đổi của ticket.
 *
 * Một ticket có nhiều comment, mỗi comment có thể kèm file đính kèm.
 * is_internal = true → comment nội bộ, chỉ staff/head/admin mới thấy.
 */
class TicketComment extends Model
{
    protected $table = 'ticket_comments';

    protected $fillable = [
        'request_id',
        'user_id',
        'user_name',
        'user_role',
        'body',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    /* ─────────── Relationships ─────────── */

    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'request_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CommentAttachment::class, 'comment_id');
    }

    /* ─────────── Scopes ─────────── */

    /**
     * Chỉ lấy comment công khai (SV được xem).
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }
}
