// ============= endpoints.php =============
<?php

use App\Repositories\Repository;
use App\Middleware\Tokens;

return function ($app): void {
    $app->group('', function ($group): void {
        $repo = new Repository();

        // Crear ticket (gestor)
        $group->post('/tickets', fn($request, $response) => $repo->registrar($request, $response));

        // Ver mis tickets (gestor)
        $group->get('/tickets/mios', fn($request, $response) => $repo->misTickets($request, $response));

        // Filtrar tickets (admin)
        $group->get('/tickets/filtrar', fn($request, $response) => $repo->filtrar($request, $response));

        // Ver todos los tickets (admin)
        $group->get('/tickets', fn($request, $response) => $repo->todos($request, $response));

        // Ver detalle de ticket
        $group->get('/tickets/{id}', fn($request, $response, $args) => $repo->detalle($request, $response, $args));

        // Cambiar estado de ticket (admin)
        $group->put('/tickets/{id}/estado', fn($request, $response, $args) => $repo->cambiarEstado($request, $response, $args));

        // Asignar ticket a admin (admin)
        $group->put('/tickets/{id}/asignar', fn($request, $response, $args) => $repo->asignar($request, $response, $args));

        // Agregar comentario al ticket (gestor o admin)
        $group->post('/tickets/{id}/comentar', fn($request, $response, $args) => $repo->comentar($request, $response, $args));

        // Ver historial de comentarios del ticket
        $group->get('/tickets/{id}/historial', fn($request, $response, $args) => $repo->historial($request, $response, $args));
    })->add(new Tokens());
};