<?php

namespace App\Repositories;

use App\Controllers\UsuarioController;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Repository
{
    private UsuarioController $ctrl;

    private array $codesError = [
        400 => 400,
        401 => 401,
        403 => 403,
        404 => 404,
        409 => 409,
        'default' => 500
    ];

    public function __construct()
    {
        $this->ctrl = new UsuarioController();
    }

    /* =====================
       MÉTODOS PÚBLICOS
    ======================*/

    public function register(Request $request, Response $response): Response
    {
        return $this->execute(function() use ($request){
            $body = $request->getParsedBody();
            return $this->ctrl->register(
                $body['name']     ?? null,
                $body['email']    ?? null,
                $body['password'] ?? null,
                $body['role']     ?? null
            );
        }, $response);
    }

    public function login(Request $request, Response $response): Response
    {
        return $this->execute(function() use ($request){
            $body = $request->getParsedBody();
            return $this->ctrl->login(
                $body['email']    ?? null,
                $body['password'] ?? null
            );
        }, $response);
    }

    public function logout(Request $request, Response $response): Response
    {
        return $this->execute(function() use ($request){
            return $this->ctrl->logout($this->extractToken($request));
        }, $response);
    }

    public function listUsers(Request $request, Response $response): Response
    {
        return $this->execute(function() use ($request){
            return $this->ctrl->getUsers($this->extractToken($request));
        }, $response);
    }

    public function updateUser(Request $request, Response $response, array $args): Response
    {
        return $this->execute(function() use ($request, $args){
            return $this->ctrl->updateUser(
                $this->extractToken($request),
                $args['id'],
                $request->getParsedBody()
            );
        }, $response);
    }

    public function changeUserRole(Request $request, Response $response, array $args): Response
    {
        return $this->execute(function() use ($request, $args){
            $body = $request->getParsedBody();
            return $this->ctrl->changeUserRole(
                $this->extractToken($request),
                $args['id'],
                $body['role'] ?? null
            );
        }, $response);
    }

    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        return $this->execute(function() use ($request, $args){
            return $this->ctrl->deleteUser(
                $this->extractToken($request),
                $args['id']
            );
        }, $response);
    }

    /* =====================
       EJECUTOR CENTRAL DE RESPUESTAS
    ======================*/

    private function execute(callable $action, Response $response): Response
    {
        try {
            $result = $action();
            return $this->success($response, $result);
        } catch (Exception $ex) {
            return $this->fail($response, $ex);
        }
    }

    private function success(Response $response, $data): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function fail(Response $response, Exception $ex): Response
    {
        $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
        $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    /* =====================
       TOKEN
    ======================*/

    private function extractToken(Request $request): string
    {
        $sources = [
            $request->getHeaderLine('Authorization'),
            $request->getServerParams()['HTTP_AUTHORIZATION'] ?? null,
            $request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        foreach ($sources as $header){
            if (!empty($header)){
                return preg_match('/Bearer\s+(.*)$/i', $header, $m)
                    ? trim($m[1])
                    : trim($header);
            }
        }
        return '';
    }
}
