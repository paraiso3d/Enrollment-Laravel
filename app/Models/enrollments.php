<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class enrollments extends Model
{
    use HasFactory;
    protected $table = 'enrollments';
    protected $fillable = [
        'student_id',
        'course_id',
        'school_year_id',
        'enrollment_date',
        'semester',
        'status',
        'is_archive',
    ];
}
