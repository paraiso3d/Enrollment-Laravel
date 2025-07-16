<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class admissions extends Model
{
    use HasFactory;
    protected $table = 'admissions';
    protected $fillable = [
        'approved_by',
        'account_id',
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
        'form_137',
        'form_138',
        'birth_certificate',
        'good_moral',
        'certificate_of_completion',


    ];

    public function account()
{
    return $this->belongsTo(accounts::class, 'account_id')->select([
        'id', 'email', 'given_name', 'surname', 'mobile_number', 'street_address', 'province', 'city', 'barangay'
    ]);
}

}
