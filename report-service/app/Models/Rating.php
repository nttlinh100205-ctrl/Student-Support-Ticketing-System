<?php

namespace App\Models;

use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'student_id',
        'department_id',
        'support_type_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'request_id' => 'integer',
        'student_id' => 'integer',
        'department_id' => 'integer',
        'support_type_id' => 'integer',
        'rating' => 'integer',
    ];
}
