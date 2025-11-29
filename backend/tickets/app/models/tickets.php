<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    protected $table = 'tickets';
    
    protected $fillable = [
        'titulo',
        'descripcion',
        'estado',
        'gestor_id',
        'admin_id'
    ];

    public $timestamps = true;

    /**
     * El ticket pertenece a un gestor (usuario)
     */
    public function gestor()
    {
        return $this->belongsTo(Users::class, 'gestor_id', 'id');
    }

    /**
     * El ticket puede estar asignado a un admin (usuario)
     */
    public function admin()
    {
        return $this->belongsTo(Users::class, 'admin_id', 'id');
    }

    /**
     * El ticket tiene muchas actividades
     */
    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'ticket_id', 'id');
    }
}
