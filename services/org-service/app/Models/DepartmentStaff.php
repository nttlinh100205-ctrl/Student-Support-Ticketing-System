<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentStaff extends Model
{
    use HasFactory;

    protected $table = 'department_staff';

    public $timestamps = false;

    protected $fillable = [
        'department_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'user_id' => 'integer',
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