<?php declare(strict_types=1);

namespace Spot\Api;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Api\Application\Application;
use Spot\Api\Application\ApplicationBuilder;
use Spot\Api\Application\ApplicationBuilderInterface;
use Spot\Api\Application\Request\HttpRequestParserRouter;
use Spot\Api\Application\Request\RequestBus;
use Spot\Api\Application\Response\ResponseBus;
use Spot\Api\Content\ApiCall\CreatePageApiCall;

class DefaultServiceProvider implements ServiceProviderInterface
{
    /** {@inheritdoc} */
    public function register(Container $container)
    {
        $container['app'] = function () use ($container) {
            /** @var  ApplicationBuilderInterface $builder */
            $builder = $container['app.builder'];

            return new Application(
                $builder->getHttpRequestParser(),
                $builder->getRequestBus(),
                $builder->getResponseBus(),
                $container['logger']
            );
        };

        $container['app.builder'] = function () use ($container) {
            $builder = new ApplicationBuilder(
                new HttpRequestParserRouter(
                    new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator()),
                    $container['logger']
                ),
                new RequestBus($container['logger']),
                new ResponseBus($container['logger'])
            );

            $builder->addApiCall(
                'POST',
                '/pages',
                CreatePageApiCall::MESSAGE,
                new CreatePageApiCall($container['repository.pages'], $container['logger'])
            );

            return $builder;
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
