<?php

use App\Repositories\Repository;
use App\Middleware\tokens;

return function ($app): void {
    $app->group('', function ($group): void {
        $repo = new repository();

        $group->post('/tickets', fn($request, $response) => $repo->registrar($request, $response));
        $group->get('/tickets/mios', fn($request, $response) => $repo->misTickets($request, $response));
        $group->get('/tickets/filtrar', fn($request, $response) => $repo->filtrar($request, $response));
        $group->get('/tickets', fn($request, $response) => $repo->todos($request, $response));
        $group->get('/tickets/{id}', fn($request, $response, $args) => $repo->detalle($request, $response, $args));
        $group->put('/tickets/{id}/estado', fn($request, $response, $args) => $repo->cambiarEstado($request, $response, $args));
        $group->put('/tickets/{id}/asignar', fn($request, $response, $args) => $repo->asignar($request, $response, $args));
        $group->post('/tickets/{id}/comentar', fn($request, $response, $args) => $repo->comentar($request, $response, $args));
        $group->get('/tickets/{id}/historial', fn($request, $response, $args) => $repo->historial($request, $response, $args));
    })->add(new tokens());
};
