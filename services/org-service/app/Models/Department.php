<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $table = 'support_departments';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function supportTypes(): HasMany
    {
        return $this->hasMany(
            SupportType::class,
            'department_id'
        );
    }

    public function staff(): HasMany
    {
        return $this->hasMany(
            DepartmentStaff::class,
            'department_id'
        );
    }
}