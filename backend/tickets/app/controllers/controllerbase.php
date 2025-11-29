<?php

namespace App\Controllers;

use App\Repositories\DataRepository;
use App\Repositories\ActividadRepository;
use App\Models\AuthToken;
use App\Models\Users;

//////////////// Clase base con utilidades para controladores de tickets

abstract class controllerbase
{
    protected $ticketRepo;
    protected $actividadRepo;

    public function __construct()
    {
        $this->ticketRepo = new DataRepository();
        $this->actividadRepo = new ActividadRepository();
    }

    // =====================================================
    //  TOKEN
    // =====================================================

    //////// Extraer token desde el request (refactorizado)
    protected function extractTokenFromRequest($request)
    {
        // Obtener header Authorization en todas las variantes posibles
        $authHeader =
            $request->getHeaderLine('Authorization')
            ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '')
            ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        // Token con formato "Bearer <token>"
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Si llega sin "Bearer"
        return trim($authHeader);
    }

    // =====================================================
    //  USUARIO DESDE TOKEN
    // =====================================================

    protected function getUserFromRequest($request)
    {
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            return null;
        }

        $auth = AuthToken::where('token', $token)->first();

        return $auth ? Users::find($auth->user_id) : null;
    }

    // =====================================================
    //  AUTORIZACIÓN
    // =====================================================

    //////// Verificar rol gestor
    protected function requireGestorRole($user)
    {
        if (!$this->hasRole($user, 'gestor')) {
            throw new \Exception('Solo los gestores pueden realizar esta acción');
        }
    }

    //////// Verificar rol admin
    protected function requireAdminRole($user)
    {
        if (!$this->hasRole($user, 'admin')) {
            throw new \Exception('Solo los administradores pueden realizar esta acción');
        }
    }

    //////// Verificación genérica de roles
    protected function hasRole($user, $role)
    {
        return $user && $user->role === $role;
    }

    // =====================================================
    //  RESPUESTAS
    // =====================================================

    //////// Respuesta de éxito
    protected function successResponse($response, $data, $statusCode = 200)
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }

    //////// Respuesta de error
    protected function errorResponse($response, $message, $statusCode = 400)
    {
        $response->getBody()->write(
            json_encode(['error' => $message], JSON_UNESCAPED_UNICODE)
        );

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}
