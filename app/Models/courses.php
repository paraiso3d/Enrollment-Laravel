<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class courses extends Model
{
    use HasFactory;
    protected $table = 'courses';
    protected $fillable = [
        'course_name',
        'course_description',
        'course_units',
        'is_archive',
    ];

}
