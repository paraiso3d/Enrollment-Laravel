<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class exam_schedules extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'academic_program_id',
        'test_permit_no',
        'room_assignment',
        'building',
        'testing_center',
        'exam_date',
        'exam_time_from',
        'exam_time_to',
        'academic_year',
    ];

    // Relationships (if needed)
    public function applicant()
    {
        return $this->belongsTo(admissions::class, 'applicant_id');
    }

    public function academicProgram()
    {
        return $this->belongsTo(courses::class, 'academic_program_id');
    }
}
