<?php

namespace App\Middleware;

use App\Models\AuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class Token   // ← AHORA COINCIDE CON use App\Middleware\Token
{
    public function __invoke(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $authHeader =
            $request->getHeaderLine('Authorization') ?: 
            ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
            ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (!$authHeader) {
            return $this->errorResponse('Token requerido', 401);
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        if (empty($token)) {
            return $this->errorResponse('Token requerido', 401);
        }

        $exists = AuthToken::where('token', $token)->first();

        if (!$exists) {
            return $this->errorResponse('Token inválido', 401);
        }

        return $handler->handle($request);
    }

    private function errorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
