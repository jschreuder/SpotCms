<?php declare(strict_types=1);

// Load autoloader & 3rd party libraries
require_once __DIR__.'/vendor/autoload.php';

// Disable error messages in output
ini_set('display_errors', 'no');

// Ensure a few local system settings
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

$container = new Pimple\Container(require __DIR__ . '/config/' . $container['environment'] . '.php');
$container['environment'] = require __DIR__. '/config/env.php';

// Register services to container
$serviceProvider = new Spot\Api\DefaultServiceProvider();
$container->register($serviceProvider);

Monolog\ErrorHandler::register($container['logger']);

return $container;
