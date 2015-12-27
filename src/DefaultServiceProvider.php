<?php declare(strict_types = 1);

namespace Spot;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Pimple\Container;
use Spot\Api\Handler\ErrorHandler;
use Spot\Api\ApplicationInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserBus;
use Spot\Api\Request\Executor\ExecutorBus;
use Spot\Api\Request\BodyParser\JsonApiParser;
use Spot\Api\Response\Generator\GeneratorBus;
use Spot\Common\ApiServiceProvider\ApiServiceProvider;
use Spot\Common\ApiServiceProvider\RepositoryProviderInterface;
use Spot\Common\ApiServiceProvider\RoutingProviderInterface;
use Spot\DataModel\Repository\ObjectRepository;

class DefaultServiceProvider implements
    RepositoryProviderInterface,
    RoutingProviderInterface
{
    /** {@inheritdoc} */
    public function init(Container $container)
    {
        $container->register(new ApiServiceProvider(
            $container,
            new HttpRequestParserBus(
                $container,
                $container['logger']
            ),
            new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator()),
            new ExecutorBus($container, $container['logger']),
            new GeneratorBus($container, $container['logger']),
            array_merge([$this], $container['modules'] ?? [])
        ));

        // Support JSON bodies for requests
        $container->extend('app', function (ApplicationInterface $application) {
            return new JsonApiParser($application);
        });

        $container['db'] = function (Container $container) {
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
    }

    public function registerRepositories(Container $container)
    {
        $container['repository.objects'] = function (Container $container) {
            return new ObjectRepository($container['db']);
        };
    }

    public function registerRouting(Container $container, ApiServiceProvider $builder)
    {
        $container['errorHandler.badRequest'] = function () {
            return new ErrorHandler('error.badRequest', 400, 'Bad Request');
        };
        $container['errorHandler.notFound'] = function () {
            return new ErrorHandler('error.notFound', 404, 'Not Found');
        };
        $container['errorHandler.serverError'] = function () {
            return new ErrorHandler('error.serverError', 500, 'Server Error');
        };

        // Add error handlers
        $jsonApiCT = 'application/vnd.api+json';
        $builder->addExecutor('error.badRequest', 'errorHandler.badRequest');
        $builder->addGenerator('error.badRequest', $jsonApiCT, 'errorHandler.badRequest');
        $builder->addExecutor('error.notFound', 'errorHandler.notFound');
        $builder->addGenerator('error.notFound', $jsonApiCT, 'errorHandler.notFound');
        $builder->addExecutor('error.serverError', 'errorHandler.serverError');
        $builder->addGenerator('error.serverError', $jsonApiCT, 'errorHandler.serverError');
    }
}
