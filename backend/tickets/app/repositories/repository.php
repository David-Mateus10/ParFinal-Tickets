<?php

namespace App\Repositories;

use App\controllers\controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Repository
{
    private controller $controller;

    public function __construct()
    {
        // Instanciamos el controlador una sola vez
        $this->controller = new controller();
    }

    /**
     * Registrar un nuevo ticket (solo gestores)
     */
    public function registrar(Request $request, Response $response)
    {
        return $this->controller->create($request, $response);
    }

    /**
     * Obtener tickets del gestor autenticado
     */
    public function misTickets(Request $request, Response $response)
    {
        return $this->controller->listMine($request, $response);
    }

    /**
     * Listar todos los tickets (solo admins)
     */
    public function todos(Request $request, Response $response)
    {
        return $this->controller->listAll($request, $response);
    }

    /**
     * Consultar detalles de un ticket específico
     */
    public function detalle(Request $request, Response $response, array $args)
    {
        return $this->controller->details($request, $response, $args);
    }

    /**
     * Cambiar el estado de un ticket (admins)
     */
    public function cambiarEstado(Request $request, Response $response, array $args)
    {
        return $this->controller->changeStatus($request, $response, $args);
    }

    /**
     * Asignar un ticket a un administrador
     */
    public function asignar(Request $request, Response $response, array $args)
    {
        return $this->controller->assign($request, $response, $args);
    }

    /**
     * Agregar un comentario a un ticket
     */
    public function comentar(Request $request, Response $response, array $args)
    {
        return $this->controller->comment($request, $response, $args);
    }

    /**
     * Consultar historial de actividades de un ticket
     */
    public function historial(Request $request, Response $response, array $args)
    {
        return $this->controller->history($request, $response, $args);
    }

    /**
     * Buscar o filtrar tickets (admins)
     */
    public function filtrar(Request $request, Response $response)
    {
        return $this->controller->search($request, $response);
    }
}
