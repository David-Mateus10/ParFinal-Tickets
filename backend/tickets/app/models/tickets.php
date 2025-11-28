<?php

namespace app\models;

use Illuminate\Database\Eloquent\Model;

class tickets extends Model
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

    // ======== RELACIONES ========

    // El creador del ticket (gestor)
    public function creadoPor()
    {
        return $this->belongsTo(Users::class, 'gestor_id');
    }

    // El administrador asignado al ticket
    public function asignadoA()
    {
        return $this->belongsTo(Users::class, 'admin_id');
    }

    // Actividades registradas en el ticket
    public function historial()
    {
        return $this->hasMany(tickets::class, 'ticket_id');
    }
}
