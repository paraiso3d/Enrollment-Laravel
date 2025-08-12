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
        'room_id',
        'building_id',
        'testing_center',
        'exam_date',
        'exam_time_from',
        'exam_time_to',
        'academic_year',
        'exam_sent'
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

    public function room()
{
    return $this->belongsTo(building_rooms::class, 'room_id');
}

public function building()
{
    return $this->belongsTo(campus_buildings::class, 'building_id');
}
}
