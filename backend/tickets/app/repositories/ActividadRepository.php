<?php

namespace App\Repositories;

use App\Models\Actividad;

class ActividadRepository
{
    ///////// Agregar una actividad/comentario a un ticket
    public function addActivity($ticketId, $userId, $mensaje)
    {
        return Actividad::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'mensaje' => $mensaje
        ]);
    }

    ///////// Obtener el historial de actividades de un ticket
    public function getTicketHistory($ticketId)
    {
        return Actividad::where('ticket_id', $ticketId)
            ->with('usuario')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
