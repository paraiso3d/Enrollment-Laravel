<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admissions extends Model
{
    use HasFactory;

    protected $table = 'admissions';

    protected $fillable = [
        'account_id',
        'applicant_number',
        'academic_year',
        'grade_level',

        'school_campus',
        'application_type',
        'classification',
        'academic_program',

        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'gender',
        'birthdate',
        'birthplace',
        'civil_status',
        'email',
        'contact_number',
        'street_address',
        'province',
        'city',
        'barangay',

        'nationality',
        'religion',
        'ethnic_affiliation',
        'telephone_number',
        'is_4ps_member',
        'is_insurance_member',
        'is_vaccinated',
        'is_indigenous',

        'last_school_attended',
        'remarks',
        'status',

        'form_137',
        'form_138',
        'birth_certificate',
        'good_moral',
        'certificate_of_completion',
    ];

    // Relationship to Account (User)
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
