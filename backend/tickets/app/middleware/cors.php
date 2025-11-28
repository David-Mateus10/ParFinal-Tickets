<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Cors
{
    /**
     * Middleware para habilitar CORS
     * Autoriza solicitudes desde cualquier origen con cabeceras comunes
     */
    public function __invoke(ServerRequestInterface $solicitud, RequestHandlerInterface $gestor): ResponseInterface
    {
        $respuesta = $gestor->handle($solicitud);

        return $respuesta
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }
}
