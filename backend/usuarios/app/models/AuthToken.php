<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthToken extends Model
{
    protected $table = 'auth_tokens';
    protected $fillable = ['user_id', 'token'];
    public $timestamps = true;

    /* =====================
       RELACIONES
    ======================*/

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    /* =====================
       MÉTODOS UTILITARIOS
    ======================*/

    /// Comprueba si un token existe
    public static function validateToken(string $token): bool
    {
        return self::where('token', $token)->exists();
    }

    /// Crea y asocia un token a un usuario
    public static function generateForUser(int $userId): string
    {
        $token = bin2hex(random_bytes(16));

        self::create([
            'user_id' => $userId,
            'token'   => $token,
        ]);

        return $token;
    }
}
