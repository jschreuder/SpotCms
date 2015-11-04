<?php

namespace Spot\Cms;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Cms\Application\Application;
use Spot\Cms\Application\Request\HttpRequestParser;
use Spot\Cms\Application\Request\RequestBus;
use Spot\Cms\Application\Response\ResponseBus;

class DefaultServiceProvider implements ServiceProviderInterface
{
    /** {@inheritdoc} */
    public function register(Container $container)
    {
        $container['app'] = function () use ($container) {
            return new Application(
                $container['app.requestParser'],
                $container['app.requestBus'],
                $container['app.responseBus'],
                $container['logger']
            );
        };

        $container['app.requestParser'] = function () use ($container) {
            return new HttpRequestParser($container['app.router'], $container['logger']);
        };

        $container['app.routeCollector'] = function () use ($container) {
            return new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator());
        };

        $container['app.router'] = function () use ($container) {
            return new GroupCountBasedDispatcher($container['app.routeCollector']->getData());
        };

        $container['app.requestBus'] = function () use ($container) {
            return new RequestBus([], $container['logger']);
        };

        $container['app.responseBus'] = function () use ($container) {
            return new ResponseBus([], $container['logger']);
        };

        $container['db'] = function () use ($container) {
            return new \PDO(
                $container['db.dsn'] . ';dbname=' . $container['db.dbname'],
                $container['db.user'],
                $container['db.pass'],
                [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]
            );
        };

        $container['logger'] = function () use ($container) {
            $logger = new Logger('spot-cms');
            $logger->pushHandler((new StreamHandler(
                __DIR__.'/../logs/frontend_'.date('Ymd').'.log',
                Logger::NOTICE
            ))->setFormatter(new LineFormatter()));
            return $logger;
        };
    }
}
