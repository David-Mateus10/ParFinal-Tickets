<?php

use Slim\Factory\AppFactory;
use App\Middleware\Cors;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';

$endpoints = require __DIR__ . '/../app/Endpoints/endpoints.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->add(new Cors());

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$endpoints($app);

$app->run();