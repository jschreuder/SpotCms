<?php declare(strict_types = 1);

// Load autoloader & 3rd party libraries
require_once __DIR__ . '/vendor/autoload.php';

// Disable error messages in output
ini_set('display_errors', 'no');

// Ensure a few local system settings
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Setup DiC with Environment config
$environment = require __DIR__ . '/config/env.php';
$container = new Pimple\Container(require __DIR__ . '/config/' . $environment . '.php');
$container['environment'] = $environment;

// Have Monolog log all PHP errors
Monolog\ErrorHandler::register($container['logger']);

// Register services to DiC
(new Spot\DefaultServiceProvider())->init($container);
(new Spot\Auth\AuthServiceProvider('/auth'))->init($container);

// Instantiate Middlewares: Final Exception handler & HSTS headers
$container->extend('app', function (\Spot\Api\ApplicationInterface $application, \Pimple\Container $container) {
    return new \Spot\Api\Middleware\ExceptionCatchingMiddleware(
        new \Spot\Api\Middleware\HstsMiddleware($application),
        $container['logger']
    );
});

return $container['app'];
