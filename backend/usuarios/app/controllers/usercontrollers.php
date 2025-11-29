<?php

namespace App\Controllers;

use App\Models\AuthToken;
use App\Models\Users;
use Exception;

class Usercontrollers
{
    /**
     * Crear un nuevo usuario
     */
    public function crear(string $nombre, string $correo, string $clave, string $rol): array
    {
        // Validaciones básicas
        if (!$nombre || !$correo || !$clave || !$rol) {
            throw new Exception("Faltan datos obligatorios", 400);
        }

        // Validar email
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de correo electrónico inválido", 400);
        }

        // Validar longitud de contraseña
        if (strlen($clave) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres", 400);
        }

        // Validar rol
        if (!in_array($rol, ['gestor', 'admin'])) {
            throw new Exception("Rol inválido. Debe ser 'gestor' o 'admin'", 400);
        }

        // Verificar si el correo ya existe
        if (Users::where('email', $correo)->exists()) {
            throw new Exception("El correo electrónico ya está registrado", 409);
        }

        // Crear usuario con contraseña hasheada
        $usuario = Users::create([
            'name'     => $nombre,
            'email'    => $correo,
            'password' => password_hash($clave, PASSWORD_BCRYPT),
            'role'     => $rol
        ]);

        // No retornar la contraseña
        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => $usuario->role,
            'created_at' => $usuario->created_at
        ];
    }

    /**
     * Autenticación de usuario
     */
    public function acceder(string $correo, string $clave): array
    {
        if (!$correo || !$clave) {
            throw new Exception("Correo y contraseña requeridos", 400);
        }

        $usuario = Users::where('email', $correo)->first();

        // Verificar que el usuario existe y la contraseña es correcta
        if (!$usuario || !password_verify($clave, $usuario->password)) {
            throw new Exception("Credenciales incorrectas", 401);
        }

        // Eliminar tokens antiguos del usuario (opcional: mantener solo una sesión activa)
        // AuthToken::where('user_id', $usuario->id)->delete();

        // Generar token aleatorio seguro
        $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales

        // Guardar token en la base de datos
        AuthToken::create([
            'user_id' => $usuario->id,
            'token'   => $token
        ]);

        return [
            'token' => $token,
            'usuario' => [
                'id'     => $usuario->id,
                'nombre' => $usuario->name,
                'correo' => $usuario->email,
                'rol'    => $usuario->role
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
                : 'Token no encontrado o ya expirado'
        ];
    }

    /**
     * Obtener todos los usuarios (solo admin)
     */
    public function listar(string $token): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->esAdministrador()) {
            throw new Exception("Acceso denegado. Solo administradores", 403);
        }

        // Retornar usuarios sin contraseña
        return Users::all()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ];
        })->toArray();
    }

    /**
     * Modificar datos de usuario (solo admin)
     */
    public function modificar(string $token, int $idUsuario, array $datos): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->esAdministrador()) {
            throw new Exception("Acceso denegado. Solo administradores", 403);
        }

        // Validar rol si se está actualizando
        if (isset($datos['role']) && !in_array($datos['role'], ['gestor', 'admin'])) {
            throw new Exception("Rol inválido. Debe ser 'gestor' o 'admin'", 400);
        }

        // Validar email si se está actualizando
        if (isset($datos['email'])) {
            if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de correo electrónico inválido", 400);
            }

            // Verificar que el email no esté en uso por otro usuario
            $existente = Users::where('email', $datos['email'])
                              ->where('id', '!=', $idUsuario)
                              ->exists();
            if ($existente) {
                throw new Exception("El correo electrónico ya está en uso", 409);
            }
        }

        // Si se está actualizando la contraseña, hashearla
        if (isset($datos['password'])) {
            if (strlen($datos['password']) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres", 400);
            }
            $datos['password'] = password_hash($datos['password'], PASSWORD_BCRYPT);
        }

        $usuarioModificar = Users::find($idUsuario);
        if (!$usuarioModificar) {
            throw new Exception("Usuario no encontrado", 404);
        }

        $usuarioModificar->update($datos);

        return [
            'mensaje' => 'Usuario actualizado correctamente',
            'usuario' => [
                'id' => $usuarioModificar->id,
                'name' => $usuarioModificar->name,
                'email' => $usuarioModificar->email,
                'role' => $usuarioModificar->role
            ]
        ];
    }

    /**
     * Cambiar rol de un usuario (solo admin)
     */
    public function cambiarRol(string $token, int $idUsuario, string $rolNuevo): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->esAdministrador()) {
            throw new Exception("Acceso denegado. Solo administradores", 403);
        }

        if (!$rolNuevo || !in_array($rolNuevo, ['gestor', 'admin'])) {
            throw new Exception("Rol inválido. Debe ser 'gestor' o 'admin'", 400);
        }

        $usuarioModificar = Users::find($idUsuario);
        if (!$usuarioModificar) {
            throw new Exception("Usuario no encontrado", 404);
        }

        $usuarioModificar->update(['role' => $rolNuevo]);

        return [
            'mensaje' => 'Rol actualizado correctamente',
            'usuario' => [
                'id' => $usuarioModificar->id,
                'name' => $usuarioModificar->name,
                'email' => $usuarioModificar->email,
                'role' => $usuarioModificar->role
            ]
        ];
    }

    /**
     * Eliminar usuario (solo admin)
     */
    public function eliminar(string $token, int $idUsuario): array
    {
        $usuario = $this->desdeToken($token);

        if (!$usuario || !$usuario->esAdministrador()) {
            throw new Exception("Acceso denegado. Solo administradores", 403);
        }

        // Evitar que el admin se elimine a sí mismo
        if ($usuario->id === $idUsuario) {
            throw new Exception("No puedes eliminar tu propia cuenta", 400);
        }

        try {
            $usuarioEliminar = Users::find($idUsuario);
            if (!$usuarioEliminar) {
                throw new Exception("Usuario no encontrado", 404);
            }

            $usuarioEliminar->delete();

            return ['mensaje' => 'Usuario eliminado correctamente'];
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("No se puede eliminar: el usuario tiene tickets asignados", 409);
            }

            throw new Exception("Error al eliminar usuario: " . $e->getMessage(), 500);
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
            throw new Exception("Token inválido o expirado", 401);
        }

        return Users::find($registro->user_id);
    }
}