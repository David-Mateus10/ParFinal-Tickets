<?php

use App\Repositories\Repository;
use App\Middleware\Token;

return function ($app): void {
    $repo = new Repository();

    // ========================================
    // RUTAS PÚBLICAS (sin autenticación)
    // ========================================

    $app->post('/usuarios/crear', fn($req, $res) => $repo->crear($req, $res));

    $app->post('/usuarios/acceder', fn($req, $res) => $repo->acceder($req, $res));

    // ========================================
    // RUTAS PROTEGIDAS (requieren token)
    // ========================================

    $app->post('/usuarios/salir', fn($req, $res) => $repo->salir($req, $res))
        ->add(new Token());

    $app->get('/usuarios', fn($req, $res) => $repo->listar($req, $res))
        ->add(new Token());

    $app->put('/usuarios/{id}', fn($req, $res, $args) => $repo->modificar($req, $res, $args))
        ->add(new Token());

    $app->patch('/usuarios/{id}/rol', fn($req, $res, $args) => $repo->cambiarRol($req, $res, $args))
        ->add(new Token());

    $app->delete('/usuarios/{id}', fn($req, $res, $args) => $repo->eliminar($req, $res, $args))
        ->add(new Token());
};