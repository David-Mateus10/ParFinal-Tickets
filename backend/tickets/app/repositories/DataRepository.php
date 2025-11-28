<?php

namespace App\Repositories;

use App\Models\tickets;

class DataRepository
{
    // ======== MÉTODOS PRINCIPALES ========

    /**
     * Registrar un nuevo ticket
     */
    public function registrar(array $data): Tickets
    {
        return tickets::create($data);
    }

    /**
     * Obtener todos los tickets creados por un gestor
     */
    public function porGestor(int $gestorId)
    {
        return tickets::where('gestor_id', $gestorId)
            ->with(['creadoPor', 'asignadoA']) // nombres refactorizados en el modelo
            ->latest()
            ->get();
    }

    /**
     * Listar todos los tickets existentes
     */
    public function todos()
    {
        return tickets::with(['creadoPor', 'asignadoA'])
            ->latest()
            ->get();
    }

    /**
     * Buscar un ticket por su ID con relaciones
     */
    public function buscarPorId(int $id)
    {
        return tickets::with(['creadoPor', 'asignadoA', 'historial.generadoPor'])
            ->find($id);
    }

    /**
     * Cambiar el estado de un ticket
     */
    public function cambiarEstado(int $id, string $estado): int
    {
        return tickets::where('id', $id)->update(['estado' => $estado]);
    }

    /**
     * Asignar un ticket a un administrador
     */
    public function asignarAdmin(int $id, int $adminId): int
    {
        return tickets::where('id', $id)->update(['admin_id' => $adminId]);
    }

    /**
     * Filtrar tickets según parámetros
     */
    public function filtrar(array $filters)
    {
        $query = tickets::with(['creadoPor', 'asignadoA']);

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['gestor_id'])) {
            $query->where('gestor_id', $filters['gestor_id']);
        }

        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        return $query->latest()->get();
    }
}
