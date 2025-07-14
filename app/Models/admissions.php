<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class admissions extends Model
{
    use HasFactory;
    protected $table = 'admissions';
    protected $fillable = [
        'school_campus',
        'academic_year',
        'application_type',
        'classification',
        'grade_level',
        'academic_program',
        'surname',
        'given_name',
        'middle_name',
        'middle_initial',
        'suffix',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'civil_status',
        'internet_connectivity',
        'learning_modality',
        'digital_literacy',
        'device',
        'street_address',
        'province',
        'city',
        'barangay',
        'nationality',
        'religion',
        'ethnic_affiliation',
        'telephone_number',
        'mobile_number',
        'email',
        'is_4ps_member',
        'is_insurance_member',
        'vacation_status',
        'is_indigenous',
    ];

}
