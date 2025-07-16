<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class accounts extends Authenticatable
{
      use HasApiTokens, Notifiable;

      protected $appends = ['profile_picture_url'];

    public function getProfilePictureUrlAttribute()
    {
        return $this->profile_picture ? asset($this->profile_picture) : null;
    }


      protected $table = 'accounts';
     protected $fillable = [
        'profile_picture',
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
        'username',
        'password',
        'verification_code',
        'is_verified',
    ];
}
