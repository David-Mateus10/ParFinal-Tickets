<?php

namespace App\Controllers;

use App\Repositories\DataRepository;
use App\Repositories\ActividadRepository;
use App\Models\AuthToken;
use App\Models\Users;

class Controller
{
    private DataRepository $ticketRepo;
    private ActividadRepository $activityRepo;

    public function __construct()
    {
        $this->ticketRepo   = new DataRepository();
        $this->activityRepo = new ActividadRepository();
    }

    // ==================== MÉTODOS PRINCIPALES ====================

    public function create($request, $response)
    {
        try {
            $data = $request->getParsedBody();
            $user = $this->getUser($request);

            $this->requireGestor($user);

            if (empty($data['titulo']) || empty($data['descripcion'])) {
                return $this->error($response, 'Título y descripción son obligatorios', 422);
            }

            $ticket = $this->ticketRepo->registrar([
                'titulo'      => $data['titulo'],
                'descripcion' => $data['descripcion'],
                'estado'      => 'abierto',
                'gestor_id'   => $user->id
            ]);

            $this->activityRepo->registrarActividad($ticket->id, $user->id, "Ticket creado: {$data['titulo']}");

            return $this->success($response, $ticket, 201);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function listMine($request, $response)
    {
        try {
            $user = $this->getUser($request);
            $this->requireGestor($user);

            $tickets = $this->ticketRepo->porGestor($user->id);
            return $this->success($response, $tickets);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function listAll($request, $response)
    {
        try {
            $user = $this->getUser($request);
            $this->requireAdmin($user);

            $tickets = $this->ticketRepo->todos();
            return $this->success($response, $tickets);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function details($request, $response, $args)
    {
        try {
            $ticketId = $args['id'] ?? null;
            $user     = $this->getUser($request);

            $ticket = $this->ticketRepo->buscarPorId($ticketId);
            if (!$ticket) {
                return $this->error($response, 'Ticket no encontrado', 404);
            }

            if ($user->esGestor() && $ticket->gestor_id !== $user->id) {
                return $this->error($response, 'No tienes permiso para ver este ticket', 403);
            }

            return $this->success($response, $ticket);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function changeStatus($request, $response, $args)
    {
        try {
            $ticketId = $args['id'] ?? null;
            $data     = $request->getParsedBody();
            $user     = $this->getUser($request);

            $this->requireAdmin($user);

            $validStates = ['abierto', 'en_progreso', 'resuelto', 'cerrado'];
            if (empty($data['estado']) || !in_array($data['estado'], $validStates)) {
                return $this->error($response, 'Estado inválido. Estados válidos: abierto, en_progreso, resuelto, cerrado', 422);
            }

            $ticket = $this->ticketRepo->buscarPorId($ticketId);
            if (!$ticket) {
                return $this->error($response, 'Ticket no encontrado', 404);
            }

            $updated = $this->ticketRepo->cambiarEstado($ticketId, $data['estado']);
            if ($updated) {
                $this->activityRepo->registrarActividad($ticketId, $user->id, "Estado cambiado a: {$data['estado']}");
                return $this->success($response, ['message' => 'Estado actualizado correctamente', 'ticket' => $this->ticketRepo->buscarPorId($ticketId)]);
            }

            return $this->error($response, 'Error al actualizar el estado', 500);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function assign($request, $response, $args)
    {
        try {
            $ticketId = $args['id'] ?? null;
            $data     = $request->getParsedBody();
            $user     = $this->getUser($request);

            $this->requireAdmin($user);

            if (empty($data['admin_id'])) {
                return $this->error($response, 'admin_id es obligatorio', 422);
            }

            $admin = Users::find($data['admin_id']);
            if (!$admin || !$admin->esAdministrador()) {
                return $this->error($response, 'El usuario especificado no es administrador', 400);
            }

            $ticket = $this->ticketRepo->buscarPorId($ticketId);
            if (!$ticket) {
                return $this->error($response, 'Ticket no encontrado', 404);
            }

            $updated = $this->ticketRepo->asignarAdmin($ticketId, $data['admin_id']);
            if ($updated) {
                $this->activityRepo->registrarActividad($ticketId, $user->id, "Ticket asignado a: {$admin->name}");
                return $this->success($response, ['message' => 'Ticket asignado correctamente', 'ticket' => $this->ticketRepo->buscarPorId($ticketId)]);
            }

            return $this->error($response, 'Error al asignar el ticket', 500);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function comment($request, $response, $args)
    {
        try {
            $ticketId = $args['id'] ?? null;
            $data     = $request->getParsedBody();
            $user     = $this->getUser($request);

            $ticket = $this->ticketRepo->buscarPorId($ticketId);
            if (!$ticket) {
                return $this->error($response, 'Ticket no encontrado', 404);
            }

            if ($user->esGestor() && $ticket->gestor_id !== $user->id) {
                return $this->error($response, 'No tienes permiso para comentar en este ticket', 403);
            }

            if (empty($data['mensaje'])) {
                return $this->error($response, 'El mensaje es obligatorio', 422);
            }

            $actividad = $this->activityRepo->registrarActividad($ticketId, $user->id, $data['mensaje']);
            return $this->success($response, $actividad, 201);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function history($request, $response, $args)
    {
        try {
            $ticketId = $args['id'] ?? null;
            $user     = $this->getUser($request);

            $ticket = $this->ticketRepo->buscarPorId($ticketId);
            if (!$ticket) {
                return $this->error($response, 'Ticket no encontrado', 404);
            }

            if ($user->esGestor() && $ticket->gestor_id !== $user->id) {
                return $this->error($response, 'No tienes permiso para ver el historial de este ticket', 403);
            }

            $history = $this->activityRepo->historialPorTicket($ticketId);
            return $this->success($response, $history);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    public function search($request, $response)
    {
        try {
            $user = $this->getUser($request);
            $this->requireAdmin($user);

            $params = $request->getQueryParams();
            $filters = array_filter([
                'estado'    => $params['estado'] ?? null,
                'gestor_id' => $params['gestor_id'] ?? null,
                'admin_id'  => $params['admin_id'] ?? null,
            ]);

            $tickets = $this->ticketRepo->filtrar($filters);
            return $this->success($response, $tickets);
        } catch (\Exception $e) {
            return $this->error($response, $e->getMessage(), 403);
        }
    }

    // ==================== MÉTODOS AUXILIARES ====================

    private function extractToken($request): string
    {
        $authHeader = $request->getHeaderLine('Authorization')
            ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '')
            ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return trim($authHeader);
    }

    private function getUser($request): ?Users
    {
        $token = $this->extractToken($request);
        if (empty($token)) {
            return null;
        }
        
        $auth = AuthToken::where('token', $token)->first();
        return $auth ? Users::find($auth->user_id) : null;
    }

    private function requireGestor(?Users $user): void
    {
        if (!$user || !$user->esGestor()) {
            throw new \Exception('Solo los gestores pueden realizar esta acción');
        }
    }

    private function requireAdmin(?Users $user): void
    {
        if (!$user || !$user->esAdministrador()) {
            throw new \Exception('Solo los administradores pueden realizar esta acción');
        }
    }

    private function success($response, $data, int $statusCode = 200)
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }

    private function error($response, string $message, int $statusCode = 400)
    {
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
}