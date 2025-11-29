<?php

namespace App\Middleware;

use App\Models\AuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class Token
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->fail("Token requerido", 401);
        }

        if (!$this->tokenExists($token)) {
            return $this->fail("Token inválido", 401);
        }

        return $handler->handle($request);
    }

    /* =====================
       MÉTODOS PRIVADOS
    ======================*/

    /// Obtiene token del header o server params
    private function extractToken(ServerRequestInterface $request): ?string
    {
        $val = $this->getAuthValue($request);

        if (!$val) {
            return null;
        }

        if (stripos($val, "Bearer ") === 0) {
            return trim(substr($val, 7));
        }

        return trim($val);
    }

    private function getAuthValue(ServerRequestInterface $request): ?string
    {
        $sources = [
            $request->getHeaderLine('Authorization'),
            $request->getServerParams()['HTTP_AUTHORIZATION'] ?? null,
            $request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        foreach ($sources as $src) {
            if (!empty($src)) {
                return $src;
            }
        }
        return null;
    }

    private function tokenExists(string $token): bool
    {
        return AuthToken::where('token', $token)->exists();
    }

    private function fail(string $message, int $status): ResponseInterface
    {
        $res = new Response();
        $res->getBody()->write(json_encode(['error' => $message]));
        return $res->withStatus($status)->withHeader("Content-Type", "application/json");
    }
}
