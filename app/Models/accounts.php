<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class accounts extends Model
{
      use HasApiTokens, Notifiable;

      protected $table = 'accounts';
      protected $fillable = [
        'username',
        'password',
        'email',
        'first_name',
        'last_name',
        'contact_number',
        'gender',
        'user_type_id',
        'verification_code',
        'is_verified',
        'is_archive',
    ];
   
}
