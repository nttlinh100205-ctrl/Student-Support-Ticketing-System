<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'is_active',
        'sla_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sla_days' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            SupportDepartment::class,
            'department_id'
        );
    }

    public function fields(): HasMany
    {
        return $this->hasMany(SupportTypeField::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
