<?php declare(strict_types = 1);

// Load autoloader & 3rd party libraries
require_once __DIR__.'/vendor/autoload.php';

// Disable error messages in output
ini_set('display_errors', 'no');

// Ensure a few local system settings
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Setup DiC with Environment config
$environment = require __DIR__. '/config/env.php';
$container = new Pimple\Container(require __DIR__ . '/config/' . $environment . '.php');
$container['environment'] = $environment;

// Have Monolog log all PHP errors
Monolog\ErrorHandler::register($container['logger']);

// Register services to DiC
$serviceProvider = new Spot\DefaultServiceProvider();
$serviceProvider->init($container);

return $container['app'];
