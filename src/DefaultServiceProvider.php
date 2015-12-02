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
use Spot\Api\ApplicationBuilder;
use Spot\Api\ApplicationInterface;
use Spot\Api\Request\HttpRequestParserRouter;
use Spot\Api\Request\RequestBus;
use Spot\Api\RequestBodyParser\JsonApiParser;
use Spot\Api\Response\ResponseBus;
use Spot\SiteContent\ApiCall\CreatePageApiCall;
use Spot\SiteContent\ApiCall\DeletePageApiCall;
use Spot\SiteContent\ApiCall\GetPageApiCall;
use Spot\SiteContent\ApiCall\ListPagesApiCall;
use Spot\SiteContent\ApiCall\UpdatePageApiCall;
use Spot\SiteContent\Repository\PageRepository;
use Spot\DataModel\Repository\ObjectRepository;

class DefaultServiceProvider implements ServiceProviderInterface
{
    /** {@inheritdoc} */
    public function register(Container $container)
    {
        $container['app'] = function (Container $container) {
            $builder = new ApplicationBuilder(
                new HttpRequestParserRouter(
                    $container,
                    $container['logger']
                ),
                new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator()),
                new RequestBus($container, $container['logger']),
                new ResponseBus($container, $container['logger'])
            );

            $this->configureApiCalls($container, $builder);
            $this->configureErrorHandlers($container, $builder);

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

    private function configureApiCalls(Container $container, ApplicationBuilder $builder)
    {
        $container['apiCall.pages.create'] = function (Container $container) {
            return new CreatePageApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.list'] = function (Container $container) {
            return new ListPagesApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.get'] = function (Container $container) {
            return new GetPageApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.update'] = function (Container $container) {
            return new UpdatePageApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.delete'] = function (Container $container) {
            return new DeletePageApiCall($container['repository.pages'], $container['logger']);
        };

        // Add ApiCalls
        $builder
            ->addApiCall('POST',   '/pages',                   CreatePageApiCall::MESSAGE, 'apiCall.pages.create')
            ->addApiCall('GET',    '/pages',                   ListPagesApiCall::MESSAGE,  'apiCall.pages.list')
            ->addApiCall('GET',    '/page/{uuid:[0-9a-z\-]+}', GetPageApiCall::MESSAGE,    'apiCall.pages.get')
            ->addApiCall('PATCH',  '/page',                    UpdatePageApiCall::MESSAGE, 'apiCall.pages.update')
            ->addApiCall('DELETE', '/page/{uuid:[0-9a-z\-]+}', DeletePageApiCall::MESSAGE, 'apiCall.pages.delete');
    }

    private function configureErrorHandlers(Container $container, ApplicationBuilder $builder)
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

    private function configureRepositories(Container $container)
    {
        $container['repository.objects'] = function (Container $container) {
            return new ObjectRepository($container['db']);
        };

        $container['repository.pages'] = function (Container $container) {
            return new PageRepository($container['db'], $container['repository.objects']);
        };
    }
}
