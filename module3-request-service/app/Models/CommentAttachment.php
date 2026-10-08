<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File đính kèm theo comment.
 * Tái sử dụng pattern tương tự RequestAttachment.
 */
class CommentAttachment extends Model
{
    protected $table = 'comment_attachments';

    protected $fillable = [
        'comment_id',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    /* ─────────── Relationships ─────────── */

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'comment_id');
    }

    /* ─────────── Helpers ─────────── */

    public function url(): string
    {
        if (! $this->comment()->exists()) {
            return '';
        }

        return route('api.requests.comments.attachments.preview', [
            'supportRequest' => $this->comment?->request_id,
            'comment' => $this->comment_id,
            'commentAttachment' => $this->id,
        ]);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
