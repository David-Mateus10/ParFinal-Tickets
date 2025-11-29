<?php

use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/database.php';

$app = AppFactory::create();

// CORS
$app->add(function (Request $request, $handler) {
    if ($request->getMethod() === "OPTIONS") {
        $response = new Response();
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Cargar rutas normales
(require __DIR__ . '/../app/endpoints/endpoints.php')($app);

// Ruta raíz
$app->get('/', function (Request $request, Response $response) {
    $data = [
        'servicio' => 'Microservicio de Tickets',
        'estado' => 'activo',
        'version' => '1.0.0',
        'fecha' => date('Y-m-d H:i:s')
    ];
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

// RUTA CATCH-ALL pero SIN OPTIONS
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'error' => 'Ruta no encontrada',
        'path' => $request->getUri()->getPath()
    ], JSON_UNESCAPED_UNICODE));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});

$app->run();
