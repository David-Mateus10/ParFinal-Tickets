<?php

namespace App\Controllers;

use App\Models\AuthToken;
use App\Models\Users;
use Exception;

class UsuarioController
{
    /**
     * Crear un nuevo usuario
     */
    public function crear(string $nombre, string $correo, string $clave, string $rol): array
    {
        if (!$nombre || !$correo || !$clave || !$rol) {
            throw new Exception("Faltan datos obligatorios", 400);
        }

        if (Users::where('email', $correo)->exists()) {
            throw new Exception("Correo ya registrado", 409);
        }

        $usuario = Users::create([
            'name'     => $nombre,
            'email'    => $correo,
            'password' => $clave,
            'role'     => $rol
        ]);

        return $usuario->toArray();
    }

    /**
     * Autenticación de usuario
     */
    public function acceder(string $correo, string $clave): array
    {
        if (!$correo || !$clave) {
            throw new Exception("Correo y clave requeridos", 400);
        }

        $usuario = Users::where('email', $correo)->first();

        if (!$usuario || $usuario->password !== $clave) {
            throw new Exception("Credenciales incorrectas", 401);
        }

        $token = bin2hex(random_bytes(16));

        AuthToken::create([
            'user_id' => $usuario->id,
            'token'   => $token
        ]);

        return [
            'token' => $token,
            'usuario' => [
                'id'    => $usuario->id,
                'nombre'=> $usuario->name,
                'correo'=> $usuario->email,
                'rol'   => $usuario->role
            ]
        ];
    }

    /**
     * Finalizar sesión
     */
    public function salir(string $token): array
    {
        if (!$token) {
            throw new Exception("Token requerido", 400);
        }

        $eliminado = AuthToken::where('token', $token)->delete();

        return [
            'mensaje' => $eliminado
                ? 'Sesión finalizada correctamente'
                : 'Sesión finalizada (token ya no existía)'
        ];
    }

    /**
     * Obtener todos los usuarios (solo admin)
     */
    public function listar(string $token): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        return Users::all()->toArray();
    }

    /**
     * Modificar datos de usuario (solo admin)
     */
    public function modificar(string $token, int $idUsuario, array $datos): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        if (isset($datos['role']) && !in_array($datos['role'], ['gestor', 'admin'])) {
            throw new Exception("Rol inválido", 400);
        }

        $actualizado = Users::where('id', $idUsuario)->update($datos);

        if (!$actualizado) {
            throw new Exception("Usuario no encontrado", 404);
        }

        return ['mensaje' => 'Usuario modificado'];
    }

    /**
     * Cambiar rol de un usuario (solo admin)
     */
    public function cambiarRol(string $token, int $idUsuario, string $rolNuevo): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        if (!$rolNuevo || !in_array($rolNuevo, ['gestor', 'admin'])) {
            throw new Exception("Rol inválido", 400);
        }

        $actualizado = Users::where('id', $idUsuario)->update(['role' => $rolNuevo]);

        if (!$actualizado) {
            throw new Exception("Usuario no encontrado", 404);
        }

        return ['mensaje' => 'Rol actualizado'];
    }

    /**
     * Eliminar usuario (solo admin)
     */
    public function eliminar(string $token, int $idUsuario): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        try {
            $eliminado = Users::destroy($idUsuario);

            if (!$eliminado) {
                throw new Exception("Usuario no encontrado", 404);
            }

            return ['mensaje' => 'Usuario eliminado'];
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("No se puede eliminar: usuario con tickets asignados", 409);
            }

            throw new Exception("Error al eliminar: " . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener usuario desde token
     */
    private function desdeToken(string $token): ?Users
    {
        if (!$token) {
            throw new Exception("Token requerido", 401);
        }

        $registro = AuthToken::where('token', $token)->first();

        if (!$registro) {
            throw new Exception("Token inválido", 401);
        }

        return Users::find($registro->user_id);
    }
}
