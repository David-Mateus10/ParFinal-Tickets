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
            ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, Origin, X-Requested-With');
    }
}
