<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportType extends Model
{
    use HasFactory;

    protected $table = 'support_types';

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }
}