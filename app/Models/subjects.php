<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subjects extends Model
{
    use HasFactory;
    protected $table = 'subjects';
       protected $fillable = [
        'course_id',
        'subject_code',
        'subject_name',
        'units',
        'semester',
        'year_level',
    ];


public function course()
{
    return $this->belongsTo(courses::class, 'course_id');
}

}
