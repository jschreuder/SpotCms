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

// Register services to DiC & Router
$container->register(new Spot\DefaultServiceProvider());
$container->register(new \Spot\Application\HttpFactoryProvider());
$container->register($siteProvider = new Spot\SiteContent\SiteContentServiceProvider($container, '/api/pages'));
$container['app.router']->registerRoutes($siteProvider);
$container->register($fileProvider = new Spot\FileManager\FileManagerServiceProvider($container, '/api/files'));
$container['app.router']->registerRoutes($fileProvider);
$container->register($imageProvider = new Spot\ImageEditor\ImageEditorServiceProvider($container, '/api/images'));
$container['app.router']->registerRoutes($imageProvider);
$container->register($authProvider = new Spot\Auth\AuthServiceProvider($container, '/api/auth'));
$container['app.router']->registerRoutes($authProvider);

// Finally: add the error catching middleware
$container->extend('app',
    function (jschreuder\Middle\ApplicationStack $application, \Pimple\Container $container) {
        return $application->withMiddleware(new jschreuder\Middle\ErrorHandlerMiddleware(
            $container['logger'],
            $container['app.error_handlers.500']
        ));
    }
);

return $container['app'];
