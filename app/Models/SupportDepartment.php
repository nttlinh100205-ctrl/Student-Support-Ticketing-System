<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tất cả tài khoản thuộc phòng ban.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    /**
     * Nhân viên của phòng ban.
     */
    public function staff()
    {
        return $this->hasMany(User::class, 'department_id')
            ->where('role', 'STAFF');
    }

    /**
     * Trưởng phòng của phòng ban.
     */
    public function heads()
    {
        return $this->hasMany(User::class, 'department_id')
            ->where('role', 'DEPARTMENT_HEAD');
    }

    public function supportTypes()
    {
        return $this->hasMany(SupportType::class, 'department_id');
    }
}
