<?php

namespace App\Repositories;

use App\Controllers\UsuarioController;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Repository
{
    private array $codigosError = [
        400 => 400,
        401 => 401,
        403 => 403,
        404 => 404,
        409 => 409,
        'default' => 500
    ];

    /**
     * Crear nuevo usuario
     */
    public function crear(Request $req, Response $res): Response
    {
        try {
            $datos = $req->getParsedBody();

            $ctrl = new UsuarioController();
            $usuario = $ctrl->crear(
                $datos['name'] ?? null,
                $datos['email'] ?? null,
                $datos['password'] ?? null,
                $datos['role'] ?? null
            );

            $res->getBody()->write(json_encode($usuario, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Iniciar sesión
     */
    public function acceder(Request $req, Response $res): Response
    {
        try {
            $datos = $req->getParsedBody();

            $ctrl = new UsuarioController();
            $resultado = $ctrl->acceder(
                $datos['email'] ?? null,
                $datos['password'] ?? null
            );

            $res->getBody()->write(json_encode($resultado, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Cerrar sesión
     */
    public function salir(Request $req, Response $res): Response
    {
        try {
            $token = $this->extraerToken($req);

            $ctrl = new UsuarioController();
            $resultado = $ctrl->salir($token);

            $res->getBody()->write(json_encode($resultado, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Listar usuarios (solo admin)
     */
    public function listar(Request $req, Response $res): Response
    {
        try {
            $token = $this->extraerToken($req);

            $ctrl = new UsuarioController();
            $usuarios = $ctrl->listar($token);

            $res->getBody()->write(json_encode($usuarios, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Modificar usuario (solo admin)
     */
    public function modificar(Request $req, Response $res, array $args): Response
    {
        try {
            $token = $this->extraerToken($req);
            $idUsuario = $args['id'];
            $datos = $req->getParsedBody();

            $ctrl = new UsuarioController();
            $resultado = $ctrl->modificar($token, $idUsuario, $datos);

            $res->getBody()->write(json_encode($resultado, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Cambiar rol de usuario (solo admin)
     */
    public function cambiarRol(Request $req, Response $res, array $args): Response
    {
        try {
            $token = $this->extraerToken($req);
            $idUsuario = $args['id'];
            $datos = $req->getParsedBody();

            $ctrl = new UsuarioController();
            $resultado = $ctrl->cambiarRol($token, $idUsuario, $datos['role'] ?? null);

            $res->getBody()->write(json_encode($resultado, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Eliminar usuario (solo admin)
     */
    public function eliminar(Request $req, Response $res, array $args): Response
    {
        try {
            $token = $this->extraerToken($req);
            $idUsuario = $args['id'];

            $ctrl = new UsuarioController();
            $resultado = $ctrl->eliminar($token, $idUsuario);

            $res->getBody()->write(json_encode($resultado, JSON_UNESCAPED_UNICODE));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            return $this->respuestaError($res, $ex);
        }
    }

    /**
     * Extraer token del request
     */
    private function extraerToken(Request $req): string
    {
        $authHeader =
            $req->getHeaderLine('Authorization') ?:
            ($req->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?:
            ($req->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return trim($authHeader);
    }

    /**
     * Respuesta de error unificada
     */
    private function respuestaError(Response $res, Exception $ex): Response
    {
        $status = $this->codigosError[$ex->getCode()] ?? $this->codigosError['default'];
        $res->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
        return $res->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
