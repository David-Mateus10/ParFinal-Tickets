<?php

use App\Repositories\Repository;
use App\Middleware\Token;

return function ($app): void {
    $repo = new Repository();

    // Registro de usuario
    $app->post('/usuarios/crear', fn($req, $res) => $repo->crear($req, $res));

    // Inicio de sesión
    $app->post('/usuarios/acceder', fn($req, $res) => $repo->acceder($req, $res));

    // Cierre de sesión
    $app->post('/usuarios/salir', fn($req, $res) => $repo->salir($req, $res))->add(new Token());

    // Listar todos los usuarios (admin)
    $app->get('/usuarios', fn($req, $res) => $repo->listar($req, $res))->add(new Token());

    // Modificar usuario (admin)
    $app->put('/usuarios/{id}', fn($req, $res, $args) => $repo->modificar($req, $res, $args))->add(new Token());

    // Cambiar rol de usuario (admin)
    $app->patch('/usuarios/{id}/rol', fn($req, $res, $args) => $repo->cambiarRol($req, $res, $args))->add(new Token());

    // Eliminar usuario (admin)
    $app->delete('/usuarios/{id}', fn($req, $res, $args) => $repo->eliminar($req, $res, $args))->add(new Token());
};
