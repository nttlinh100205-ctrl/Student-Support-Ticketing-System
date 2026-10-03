<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTypeField extends Model
{
    protected $fillable = [
        'support_type_id',
        'field_key',
        'label',
        'field_type',
        'is_required',
        'options',
        'help_text',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    public function supportType(): BelongsTo
    {
        return $this->belongsTo(SupportType::class);
    }
}
