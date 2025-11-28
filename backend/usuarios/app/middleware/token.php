<?php

namespace App\Middleware;

use App\Models\AuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class Token
{
    public function __invoke(
        ServerRequestInterface $solicitud,
        RequestHandlerInterface $gestor
    ): ResponseInterface {
        // Extraer encabezado Authorization desde múltiples fuentes
        $encabezado =
            $solicitud->getHeaderLine('Authorization') ?:
            ($solicitud->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?:
            ($solicitud->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (!$encabezado) {
            return $this->respuestaError('Token no proporcionado', 401);
        }

        // Obtener el token limpio
        $token = preg_match('/Bearer\s+(.*)$/i', $encabezado, $coincidencias)
            ? trim($coincidencias[1])
            : trim($encabezado);

        if (!$token) {
            return $this->respuestaError('Token vacío o mal formado', 401);
        }

        // Validar existencia del token en la base de datos
        $registro = AuthToken::where('token', $token)->first();

        if (!$registro) {
            return $this->respuestaError('Token inválido', 403);
        }

        // Token válido, continuar con la petición
        return $gestor->handle($solicitud);
    }

    private function respuestaError(string $mensaje, int $codigo): ResponseInterface
    {
        $respuesta = new Response();
        $respuesta->getBody()->write(json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE));

        return $respuesta
            ->withStatus($codigo)
            ->withHeader('Content-Type', 'application/json');
    }
}
