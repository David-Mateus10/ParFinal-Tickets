<?php

namespace App\Repositories;


use app\models\actividad;


class ActividadRepository
{
    // ======== MÉTODOS PRINCIPALES ========

    /**
     * Registrar una nueva actividad o comentario en un ticket
     */
    public function registrarActividad(int $ticketId, int $userId, string $mensaje): actividad
    {
        return actividad::create([
            'ticket_id' => $ticketId,
            'user_id'   => $userId,
            'mensaje'   => $mensaje
        ]);
    }

    /**
     * Consultar el historial completo de un ticket
     */
    public function historialPorTicket(int $ticketId)
    {
        return actividad::where('ticket_id', $ticketId)
            ->with('generadoPor') // relación renombrada en el modelo
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
