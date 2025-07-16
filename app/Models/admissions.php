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


        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'birthdate',
        'birthplace',
        'email',
        'contact_number',
        'street_address',
        'province',
        'city',
        'barangay',

        // Academic background (if applicable)
        'strand',
        'lrn',
        'last_school_attended',

        // System/processing fields
        'school_year',
        'status',
        'remarks',

        //Files
        'form_137_path',
        'form_138_path',
        'birth_certificate_path',
        'good_moral_path',
        'cortificate_of_completion_path',


    ];
}
