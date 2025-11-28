<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'ticket_actividad';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'mensaje'
    ];

    public $timestamps = true;

    public function generadoPor()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
