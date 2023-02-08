<?php declare(strict_types = 1);

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

$app = require __DIR__ . '/../config/app_init.php';

// Create request from globals
$request = ServerRequestFactory::fromGlobals();

// Execute the application
$response = $app->process($request);

// Output the response
(new SapiEmitter())->emit($response);
