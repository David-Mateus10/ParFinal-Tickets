<?php

namespace App\Models;

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

    // Verificar si el usuario es administrador
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Verificar si el usuario es gestor
    public function isGestor()
    {
        return $this->role === 'gestor';
    }

    // Usuario tiene muchos tickets creados como gestor
    public function ticketsCreados()
    {
        return $this->hasMany(Tickets::class, 'gestor_id', 'id');
    }

    // Usuario tiene muchos tickets asignados como admin
    public function ticketsAsignados()
    {
        return $this->hasMany(Tickets::class, 'admin_id', 'id');
    }

    // Usuario tiene muchas actividades
    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'user_id', 'id');
    }
}
