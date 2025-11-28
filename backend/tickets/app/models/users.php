<?php

namespace app\models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password'
    ];

    public $timestamps = true;

    // ======== MÉTODOS DE ROL ========

    public function esAdministrador(): bool
    {
        return $this->role === 'admin';
    }

    public function esGestor(): bool
    {
        return $this->role === 'gestor';
    }

    // ======== RELACIONES ========

    // Tickets donde el usuario es gestor
    public function gestionados()
    {
        return $this->hasMany(Tickets::class, 'gestor_id');
    }

    // Tickets donde el usuario es el administrador asignado
    public function asignaciones()
    {
        return $this->hasMany(Tickets::class, 'admin_id');
    }

    // Actividades registradas por el usuario
    public function registroActividades()
    {
        return $this->hasMany(tickets::class, 'user_id');
    }
}
