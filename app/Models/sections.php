<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sections extends Model
{
    use HasFactory;
    protected $table = 'sections';
    protected $fillable = [
        'section_name',
        'course_id',
        'school_year_id',
        'instructor_id',
        'is_archive',  
    ];


    public function instructor()
{
    return $this->belongsTo(accounts::class, 'instructor_id'); // adjust model if needed
}

public function course()
{
    return $this->belongsTo(courses::class, 'course_id');
}

public function schoolYear()
{
    return $this->belongsTo(school_years::class, 'school_year_id');
}

}
