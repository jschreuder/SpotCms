<?php declare(strict_types = 1);

use jschreuder\MiddleDi\ConfigTrait;
use Spot\Auth\AuthRoutingProvider;
use Spot\Auth\AuthServiceProviderInterface;
use Spot\DefaultServiceProvider;
use Spot\FileManager\FileManagerRoutingProvider;
use Spot\FileManager\FileManagerServiceProvider;
use Spot\FileManager\FileManagerServiceProviderInterface;
use Spot\ImageEditor\ImageEditorRoutingProvider;
use Spot\ImageEditor\ImageEditorServiceProvider;
use Spot\ImageEditor\ImageEditorServiceProviderInterface;
use Spot\SiteContent\SiteContentRoutingProvider;
use Spot\SiteContent\SiteContentServiceProvider;
use Spot\SiteContent\SiteContentServiceProviderInterface;

// Load autoloader & 3rd party libraries
require_once __DIR__ . '/vendor/autoload.php';

// Disable error messages in output
ini_set('display_errors', 'no');

// Ensure a few local system settings
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// Fetch environment and configuration
$environment = require __DIR__ . '/config/env.php';
$config = require __DIR__ . '/config/' . $environment . '.php';
$config['environment'] = $environment;

// Setup service container
$container = new class($config) implements 
    SiteContentServiceProviderInterface,
    FileManagerServiceProviderInterface,
    ImageEditorServiceProviderInterface,
    AuthServiceProviderInterface
{
    use ConfigTrait;
    use DefaultServiceProvider;
    use SiteContentServiceProvider;
    use FileManagerServiceProvider;
    use ImageEditorServiceProvider;
    use AuthRoutingProvider;
};

// Have Monolog log all PHP errors
Monolog\ErrorHandler::register($container->getLogger());

// Register routes
$router = $container->getRouter();
$router->registerRoutes(new SiteContentRoutingProvider('/api/pages', $container));
$router->registerRoutes(new FileManagerRoutingProvider('/api/files', $container));
$router->registerRoutes(new ImageEditorRoutingProvider('/api/images', $container));
$router->registerRoutes(new AuthRoutingProvider('/api/auth', $container));

return $container->getApp();
