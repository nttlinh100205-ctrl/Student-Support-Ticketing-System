<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        return Storage::disk('public')->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
