<?php

namespace App\Controllers;

use App\Models\Users;

class controlleradmin extends controllerbase
{
    // =====================================================
    // LISTAR TODOS LOS TICKETS
    // =====================================================
    public function listAllTickets($request, $response)
    {
        $this->authorizeAdmin($request);

        $tickets = $this->ticketRepo->getAllTickets();

        return $this->successResponse($response, $tickets);
    }

    // =====================================================
    // BUSCAR / FILTRAR TICKETS
    // =====================================================
    public function searchTickets($request, $response)
    {
        $this->authorizeAdmin($request);

        $params = $request->getQueryParams();

        // filtros permitidos
        $allowed = ['estado', 'gestor_id', 'admin_id'];
        $filters = array_intersect_key($params, array_flip($allowed));

        $tickets = $this->ticketRepo->searchTickets($filters);

        return $this->successResponse($response, $tickets);
    }

    // =====================================================
    // DETALLES DE TICKET
    // =====================================================
    public function getTicketDetails($request, $response, $args)
    {
        $this->authorizeAdmin($request);

        $ticket = $this->findTicketOrError($response, $args['id']);
        if (!$ticket) return $ticket;

        return $this->successResponse($response, $ticket);
    }

    // =====================================================
    // ACTUALIZAR ESTADO
    // =====================================================
    public function updateTicketStatus($request, $response, $args)
    {
        $user = $this->authorizeAdmin($request);
        $data = $request->getParsedBody();

        // Validación de estado
        if (!$this->isValidStatus($data['estado'] ?? null)) {
            return $this->errorResponse(
                $response,
                'Estado inválido. Valores permitidos: abierto, en_progreso, resuelto, cerrado',
                400
            );
        }

        $ticket = $this->findTicketOrError($response, $args['id']);
        if (!$ticket) return $ticket;

        $updated = $this->ticketRepo->updateTicketStatus($args['id'], $data['estado']);

        if ($updated) {
            $this->actividadRepo->addActivity(
                $args['id'],
                $user->id,
                'Estado cambiado a: ' . $data['estado']
            );
            return $this->successResponse($response, ['message' => 'Estado actualizado correctamente']);
        }

        return $this->errorResponse($response, 'Error al actualizar el estado', 500);
    }

    // =====================================================
    // ASIGNAR TICKET A UN ADMIN
    // =====================================================
    public function assignTicket($request, $response, $args)
    {
        $user = $this->authorizeAdmin($request);
        $data = $request->getParsedBody();

        if (empty($data['admin_id'])) {
            return $this->errorResponse($response, 'admin_id es obligatorio', 400);
        }

        $admin = Users::find($data['admin_id']);

        if (!$admin || $admin->role !== 'admin') {
            return $this->errorResponse($response, 'El usuario especificado no es un administrador', 400);
        }

        $ticket = $this->findTicketOrError($response, $args['id']);
        if (!$ticket) return $ticket;

        $updated = $this->ticketRepo->assignTicket($args['id'], $data['admin_id']);

        if ($updated) {
            $this->actividadRepo->addActivity(
                $args['id'],
                $user->id,
                'Ticket asignado a: ' . $admin->name
            );
            return $this->successResponse($response, ['message' => 'Ticket asignado correctamente']);
        }

        return $this->errorResponse($response, 'Error al asignar el ticket', 500);
    }

    // =====================================================
    // AGREGAR COMENTARIO
    // =====================================================
    public function addComment($request, $response, $args)
    {
        $user = $this->authorizeAdmin($request);
        $data = $request->getParsedBody();

        if (empty($data['mensaje'])) {
            return $this->errorResponse($response, 'El mensaje es obligatorio', 400);
        }

        $ticket = $this->findTicketOrError($response, $args['id']);
        if (!$ticket) return $ticket;

        $actividad = $this->actividadRepo->addActivity(
            $args['id'],
            $user->id,
            $data['mensaje']
        );

        return $this->successResponse($response, $actividad, 201);
    }

    // =====================================================
    // HISTORIAL DE ACTIVIDAD
    // =====================================================
    public function getTicketHistory($request, $response, $args)
    {
        $this->authorizeAdmin($request);

        $ticket = $this->findTicketOrError($response, $args['id']);
        if (!$ticket) return $ticket;

        $history = $this->actividadRepo->getTicketHistory($args['id']);

        return $this->successResponse($response, $history);
    }


    // =====================================================
    // MÉTODOS PRIVADOS -> ayudan a limpiar la lógica
    // =====================================================

    private function authorizeAdmin($request)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);
        return $user;
    }

    private function findTicketOrError($response, $ticketId)
    {
        $ticket = $this->ticketRepo->getTicketById($ticketId);

        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        return $ticket;
    }

    private function isValidStatus($estado)
    {
        $validos = ['abierto', 'en_progreso', 'resuelto', 'cerrado'];
        return in_array($estado, $validos, true);
    }
}
