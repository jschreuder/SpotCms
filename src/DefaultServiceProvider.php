<?php declare(strict_types = 1);

namespace Spot;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Pimple\Container;
use Spot\Api\Handler\ErrorHandler;
use Spot\Api\ApplicationInterface;
use Spot\Api\Middleware\JsonApiRequestParser;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserBus;
use Spot\Api\Request\Executor\ExecutorBus;
use Spot\Api\Response\Generator\GeneratorBus;
use Spot\Api\ApplicationServiceProvider;
use Spot\Api\ServiceProvider\RepositoryProviderInterface;
use Spot\Api\ServiceProvider\RoutingProviderInterface;
use Spot\DataModel\Repository\ObjectRepository;

class DefaultServiceProvider implements
    RepositoryProviderInterface,
    RoutingProviderInterface
{
    /** {@inheritdoc} */
    public function init(Container $container)
    {
        $container->register((new ApplicationServiceProvider(
            $container,
            new HttpRequestParserBus(
                $container,
                $container['logger']
            ),
            new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator()),
            new ExecutorBus($container, $container['logger']),
            new GeneratorBus($container, $container['logger'])
        ))->addModule($this)->addModules($container['modules'] ?? []));

        // Support JSON bodies for requests
        $container->extend('app', function (ApplicationInterface $application) {
            return new JsonApiRequestParser($application);
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

    public function registerRouting(Container $container, ApplicationServiceProvider $builder)
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
