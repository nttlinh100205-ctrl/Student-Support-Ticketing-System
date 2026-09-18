<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RequestAttachment extends Model
{
    protected $fillable = [
        'request_id',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function request()
    {
        return $this->belongsTo(SupportRequest::class, 'request_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
