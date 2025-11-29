<?php

namespace App\Controllers;

use App\Models\AuthToken;
use App\Models\Users;
use Exception;

class UsuarioController
{
    /// Registrar usuario
    public function register($name, $email, $password, $role)
    {
        $this->ensureRequired([$name, $email, $password, $role], "Todos los campos son obligatorios");

        if ($this->emailExists($email)) {
            throw new Exception("El email ya está asociado a una cuenta", 409);
        }

        $user = Users::create(compact('name', 'email', 'password', 'role'));

        return $user->toArray();
    }

    /// Login usuario
    public function login($email, $password)
    {
        $this->ensureRequired([$email, $password], "Debe proporcionar email y contraseña");

        $user = Users::where('email', $email)->first();

        if (!$user || $user->password !== $password) {
            throw new Exception("Credenciales incorrectas", 401);
        }

        $token = $this->createToken($user->id);

        return [
            'token' => $token,
            'user'  => $this->serializeUser($user)
        ];
    }

    /// Logout usuario
    public function logout($token)
    {
        $this->ensureToken($token);

        $deleted = AuthToken::where('token', $token)->delete();

        return ['message' => $deleted ? 
            'Sesión cerrada' : 
            'El token ya no existía'
        ];
    }

    /// Obtener lista de usuarios (admin)
    public function getUsers($token)
    {
        $this->ensureAdmin($token);

        return Users::all()->toArray();
    }

    /// Actualizar usuario
    public function updateUser($token, $userId, $data)
    {
        $this->ensureAdmin($token);

        if (isset($data['role']) && !$this->validRole($data['role'])) {
            throw new Exception("Rol inválido", 400);
        }

        if (!Users::where('id', $userId)->update($data)) {
            throw new Exception("Usuario no encontrado", 404);
        }

        return ['message' => 'Usuario actualizado'];
    }

    /// Cambiar rol
    public function changeUserRole($token, $userId, $newRole)
    {
        $this->ensureAdmin($token);

        if (!$this->validRole($newRole)) {
            throw new Exception("Rol no válido", 400);
        }

        if (!Users::where('id', $userId)->update(['role' => $newRole])) {
            throw new Exception("Usuario no encontrado", 404);
        }

        return ['message' => 'Rol modificado correctamente'];
    }

    /// Eliminar usuario
    public function deleteUser($token, $userId)
    {
        $this->ensureAdmin($token);

        try {
            $deleted = Users::destroy($userId);

            if (!$deleted) {
                throw new Exception("Usuario no encontrado", 404);
            }

            return ['message' => 'Usuario eliminado'];
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                throw new Exception("No puede eliminarse, tiene tickets asignados.", 409);
            }
            throw $e;
        }
    }

    /* =====================
       MÉTODOS PRIVADOS
    ======================*/

    private function ensureRequired($fields, $error)
    {
        foreach ($fields as $field) {
            if (empty($field)) {
                throw new Exception($error, 400);
            }
        }
    }

    private function emailExists($email)
    {
        return Users::where('email', $email)->first() !== null;
    }

    private function createToken($userId)
    {
        $token = bin2hex(random_bytes(16));
        AuthToken::create(['user_id' => $userId, 'token' => $token]);
        return $token;
    }

    private function serializeUser($user)
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role
        ];
    }

    private function ensureToken($token)
    {
        if (!$token) {
            throw new Exception("Token requerido", 400);
        }
    }

    private function ensureAdmin($token)
    {
        $user = $this->getUserFromToken($token);

        if (!$user || !$user->isAdmin()) {
            throw new Exception("Permisos insuficientes", 403);
        }
    }

    private function validRole($role)
    {
        return in_array($role, ['gestor', 'admin']);
    }

    private function getUserFromToken($token)
    {
        $this->ensureToken($token);

        $auth = AuthToken::where('token', $token)->first();

        if (!$auth) {
            throw new Exception("Token inválido", 401);
        }

        return Users::find($auth->user_id);
    }
}
