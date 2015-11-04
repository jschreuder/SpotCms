<?php

/** @var  Pimple\Container $container */
$container = require __DIR__.'/../env_init.php';

/** @var  Spot\Cms\Application\ApplicationInterface $app */
$app = $container['app'];

// Setup request, force JSON decoding on body
$request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

// Execute the application
$response = $app->execute($request);

// Output the response
(new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
