<?php declare(strict_types = 1);

/** @var  jschreuder\Middle\ApplicationStackInterface $app */
$app = require __DIR__ . '/../app_init.php';

// Create request from globals
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();

// Execute the application
$response = $app->process($request);

// Output the response
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);
