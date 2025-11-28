<?php

namespace app\middleware;

use app\models\AuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class tokens
{
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Extraer encabezado Authorization desde múltiples fuentes
        $authHeader =
            $request->getHeaderLine('Authorization') ?:
            ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?:
            ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (!$authHeader) {
            return $this->respuestaError('Token requerido', 401);
        }

        // Obtener el token limpio
        $token = preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)
            ? trim($matches[1])
            : trim($authHeader);

        if (empty($token)) {
            return $this->respuestaError('Token requerido', 401);
        }

        // Validar existencia del token en la base de datos
        $registro = AuthToken ::where('token', $token)->first();
        if (!$registro) {
            return $this->respuestaError('Token inválido', 403);
        }

        // Token válido, continuar con la petición
        return $handler->handle($request);
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
