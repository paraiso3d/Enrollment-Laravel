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
    ];


public function course()
{
    return $this->belongsTo(Courses::class, 'course_id');
}

}
