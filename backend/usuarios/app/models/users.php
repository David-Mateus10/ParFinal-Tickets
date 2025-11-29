<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden   = ['password'];

    public $timestamps = true;

    /* =====================
       RELACIONES
    ======================*/

    public function tokens()
    {
        return $this->hasMany(AuthToken::class, 'user_id');
    }

    /* =====================
       ROLES
    ======================*/

    public function hasRole(string $role): bool
    {
        return strtolower($this->role) === strtolower($role);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isGestor(): bool
    {
        return $this->hasRole('gestor');
    }

    public function roleLabel(): string
    {
        return ucfirst($this->role);
    }

    /* =====================
       UTILITARIOS
    ======================*/

    public static function findByEmail(string $email): ?self
    {
        return self::where('email', $email)->first();
    }

    public function visibleData(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role
        ];
    }
}
