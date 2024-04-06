<?php

namespace App\WRLA\Models;

use \Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'permissions',
        'settings',
        'data'
    ];
}