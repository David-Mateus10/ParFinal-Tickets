<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthToken extends Model
{
    protected $table = 'auth_tokens'; // o el nombre real de tu tabla
    protected $fillable = ['user_id', 'token'];
    public $timestamps = false;
}
