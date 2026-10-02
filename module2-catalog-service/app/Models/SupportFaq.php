<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportFaq extends Model
{
    protected $fillable = [
        'department_id',
        'support_type_id',
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'support_type_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            SupportDepartment::class,
            'department_id'
        );
    }

    public function supportType(): BelongsTo
    {
        return $this->belongsTo(
            SupportType::class,
            'support_type_id'
        );
    }
}