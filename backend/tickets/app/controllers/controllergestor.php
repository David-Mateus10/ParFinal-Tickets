<?php

namespace App\Controllers;

class controllergestor extends controllerbase
{
    // =====================================================
    // CREAR TICKET
    // =====================================================
    public function createTicket($request, $response)
    {
        $user = $this->authorizeGestor($request);
        $data = $request->getParsedBody();

        // Validación
        if (empty($data['titulo']) || empty($data['descripcion'])) {
            return $this->errorResponse($response, 'Título y descripción son obligatorios', 400);
        }

        // Crear ticket
        $ticket = $this->ticketRepo->createTicket([
            'titulo'      => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'estado'      => 'abierto',
            'gestor_id'   => $user->id
        ]);

        // Actividad inicial
        $this->actividadRepo->addActivity(
            $ticket->id,
            $user->id,
            'Ticket creado: ' . $data['titulo']
        );

        return $this->successResponse($response, $ticket, 201);
    }

    // =====================================================
    // LISTAR MIS TICKETS
    // =====================================================
    public function listMyTickets($request, $response)
    {
        $user = $this->authorizeGestor($request);

        $tickets = $this->ticketRepo->getTicketsByGestor($user->id);

        return $this->successResponse($response, $tickets);
    }

    // =====================================================
    // VER DETALLES DE UN TICKET PROPIO
    // =====================================================
    public function getTicketDetails($request, $response, $args)
    {
        $user = $this->authorizeGestor($request);

        $ticket = $this->findMyTicketOrError($response, $args['id'], $user->id);
        if (!$ticket) return $ticket;

        return $this->successResponse($response, $ticket);
    }

    // =====================================================
    // COMENTAR UN TICKET PROPIO
    // =====================================================
    public function addComment($request, $response, $args)
    {
        $user = $this->authorizeGestor($request);
        $data = $request->getParsedBody();

        if (empty($data['mensaje'])) {
            return $this->errorResponse($response, 'El mensaje es obligatorio', 400);
        }

        $ticket = $this->findMyTicketOrError($response, $args['id'], $user->id);
        if (!$ticket) return $ticket;

        $actividad = $this->actividadRepo->addActivity(
            $args['id'],
            $user->id,
            $data['mensaje']
        );

        return $this->successResponse($response, $actividad, 201);
    }

    // =====================================================
    // HISTORIAL DE ACTIVIDAD DE UN TICKET PROPIO
    // =====================================================
    public function getTicketHistory($request, $response, $args)
    {
        $user = $this->authorizeGestor($request);

        $ticket = $this->findMyTicketOrError($response, $args['id'], $user->id);
        if (!$ticket) return $ticket;

        $history = $this->actividadRepo->getTicketHistory($args['id']);

        return $this->successResponse($response, $history);
    }


    // =====================================================
    // MÉTODOS PRIVADOS - Limpian y centralizan lógica
    // =====================================================

    private function authorizeGestor($request)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireGestorRole($user);
        return $user;
    }

    private function findMyTicketOrError($response, $ticketId, $gestorId)
    {
        $ticket = $this->ticketRepo->getTicketById($ticketId);

        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        if ($ticket->gestor_id != $gestorId) {
            return $this->errorResponse($response, 'No tienes permiso para acceder a este ticket', 403);
        }

        return $ticket;
    }
}
