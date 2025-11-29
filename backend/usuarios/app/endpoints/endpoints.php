<?php

use App\Repositories\Repository;
use App\Middleware\Token;

return function ($app) {

    // Ruta raíz para evitar Not Found
    $app->get('/', function ($req, $res) {
        $res->getBody()->write("API Usuarios funcionando ✔️");
        return $res->withHeader("Content-Type", "text/plain");
    });

    $map = function($class){
        return new $class();
    };

    // Helper para endpoints con token
    $secure = function($callable){
        return $callable->add(new Token());
    };

    // Registro
    $app->post('/register', function ($request, $response) use ($map) {
        return $map(Repository::class)->register($request, $response);
    });

    // Login
    $app->post('/login', function ($request, $response) use ($map) {
        return $map(Repository::class)->login($request, $response);
    });

    // Logout (requiere token)
    $secure(
        $app->post('/logout', function ($request, $response) use ($map) {
            return $map(Repository::class)->logout($request, $response);
        })
    );

    // Listar usuarios (requiere token)
    $secure(
        $app->get('/users', function ($request, $response) use ($map) {
            return $map(Repository::class)->listUsers($request, $response);
        })
    );

    // Actualizar usuario (requiere token)
    $secure(
        $app->put('/users/{id}', function ($request, $response, $args) use ($map) {
            return $map(Repository::class)->updateUser($request, $response, $args);
        })
    );

    // Cambiar rol (requiere token)
    $secure(
        $app->patch('/users/{id}/role', function ($request, $response, $args) use ($map) {
            return $map(Repository::class)->changeUserRole($request, $response, $args);
        })
    );

    // Eliminar usuario (requiere token)
    $secure(
        $app->delete('/users/{id}', function ($request, $response, $args) use ($map) {
            return $map(Repository::class)->deleteUser($request, $response, $args);
        })
    );
};
