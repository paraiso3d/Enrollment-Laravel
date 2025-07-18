<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user_types extends Model
{
    use HasFactory;
    protected $table = 'user_types';
    protected $fillable = [
        'role_name',
        'description',
        'is_archive',
    ];
}
