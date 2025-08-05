<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class exam_schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'academic_program_id',
        'test_permit_no',
        'room',
        'building',
        'testing_center',
        'exam_date',
        'start_time',
        'end_time',
        'school_year',
    ];

    // Relationships (if needed)
    public function applicant()
    {
        return $this->belongsTo(Admissions::class, 'applicant_id');
    }

    public function academicProgram()
    {
        return $this->belongsTo(courses::class, 'academic_program_id');
    }
}
