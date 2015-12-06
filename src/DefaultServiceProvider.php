<?php declare(strict_types=1);

namespace Spot;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Api\ApiCall\ErrorApiCall;
use Spot\Api\Application;
use Spot\Api\ApplicationInterface;
use Spot\Api\Request\HttpRequestParserRouter;
use Spot\Api\Request\RequestBus;
use Spot\Api\RequestBodyParser\JsonApiParser;
use Spot\Api\Response\ResponseBus;
use Spot\Common\ApiBuilder\ApiBuilder;
use Spot\Common\ApiBuilder\RepositoryBuilderInterface;
use Spot\Common\ApiBuilder\RouterBuilderInterface;
use Spot\DataModel\Repository\ObjectRepository;

class DefaultServiceProvider implements
    ServiceProviderInterface,
    RouterBuilderInterface,
    RepositoryBuilderInterface
{
    /** {@inheritdoc} */
    public function register(Container $container)
    {
        $container['app'] = function (Container $container) {
            $builder = new ApiBuilder(
                $container,
                new HttpRequestParserRouter(
                    $container,
                    $container['logger']
                ),
                new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator()),
                new RequestBus($container, $container['logger']),
                new ResponseBus($container, $container['logger']),
                array_merge([$this], $container['modules'] ?? [])
            );

            return new Application(
                $builder->getHttpRequestParser(),
                $builder->getRequestBus(),
                $builder->getResponseBus(),
                $container['logger']
            );
        };

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

        $this->configureRepositories($container);

        $container['logger'] = function () {
            $logger = new Logger('spot-api');
            $logger->pushHandler((new StreamHandler(
                __DIR__.'/../logs/'.date('Ymd').'.log',
                Logger::NOTICE
            ))->setFormatter(new LineFormatter()));
            return $logger;
        };
    }

    public function configureRouting(Container $container, ApiBuilder $builder)
    {
        $container['errorHandler.badRequest'] = function () {
            return new ErrorApiCall('error.badRequest', 400, 'Bad Request');
        };
        $container['errorHandler.notFound'] = function () {
            return new ErrorApiCall('error.notFound', 404, 'Not Found');
        };
        $container['errorHandler.serverError'] = function () {
            return new ErrorApiCall('error.serverError', 500, 'Server Error');
        };

        // Add error handlers
        $builder->addRequestExecutor('error.badRequest', 'errorHandler.badRequest');
        $builder->addResponseGenerator('error.badRequest', 'errorHandler.badRequest');
        $builder->addRequestExecutor('error.notFound', 'errorHandler.notFound');
        $builder->addResponseGenerator('error.notFound', 'errorHandler.notFound');
        $builder->addRequestExecutor('error.serverError', 'errorHandler.serverError');
        $builder->addResponseGenerator('error.serverError', 'errorHandler.serverError');
    }

    public function configureRepositories(Container $container)
    {
        $container['repository.objects'] = function (Container $container) {
            return new ObjectRepository($container['db']);
        };
    }
}
