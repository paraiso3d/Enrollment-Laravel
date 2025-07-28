<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class admissions extends Model
{
    use HasFactory;
    protected $table = 'admissions';
   protected $fillable = [
    'applicant_number',
    'account_id',
    'school_campus',
    'academic_year',
    'academic_program',
    'application_type',
    'classification',
    'grade_level', // optional for SHS

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

    'lrn',
    'last_school_attended',

    'school_year',
    'status',
    'remarks',

    'form_137_path',
    'form_138_path',
    'birth_certificate_path',
    'good_moral_path',
    'certificate_of_completion_path',
    'is_admitted', // New field to track admission status
];


    public function account()
{
    return $this->belongsTo(accounts::class, 'account_id')->select([
        'id', 'email', 'given_name', 'surname', 'mobile_number', 'street_address', 'province', 'city', 'barangay'
    ]);
    
}

public function course()
{
    return $this->belongsTo(courses::class);
}


}
