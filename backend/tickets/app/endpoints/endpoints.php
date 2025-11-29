<?php

use App\Repositories\Repository;
use App\Middleware\Token;
use App\Middleware\Cors;

return function ($app) {

    // REGISTRAR CORS UNA SOLA VEZ
    $app->add(new Cors());

    // SOLO UNA DEFINICIÓN OPTIONS EN TODO EL PROYECTO
    $app->options('/{routes:.+}', function ($req, $res) {
        return $res;
    });

    $map = fn($class) => new $class();
    $secure = fn($callable) => $callable->add(new Token());

    // ENDPOINTS EJEMPLO
    $secure(
        $app->get('/tickets', function ($request, $response) use ($map) {
            return $map(Repository::class)->listTickets($request, $response);
        })
    );

    $secure(
        $app->post('/tickets', function ($request, $response) use ($map) {
            return $map(Repository::class)->createTicket($request, $response);
        })
    );
};
