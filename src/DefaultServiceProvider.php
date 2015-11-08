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
use Spot\Api\Application\ApplicationInterface;
use Spot\Api\Application\Request\HttpRequestParserRouter;
use Spot\Api\Application\Request\RequestBus;
use Spot\Api\Application\Response\ResponseBus;
use Spot\Api\Common\RequestBodyParser\JsonParser;
use Spot\Api\Content\ApiCall\CreatePageApiCall;

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

            return new Application(
                $builder->getHttpRequestParser(),
                $builder->getRequestBus(),
                $builder->getResponseBus(),
                $container['logger']
            );
        };

        // Support JSON bodies for requests
        $container->extend('app', function (ApplicationInterface $application) {
            return new JsonParser($application);
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

        $builder
            ->addApiCall('POST',   '/pages',                   CreatePageApiCall::MESSAGE, 'apiCall.pages.create')
            ->addApiCall('GET',    '/pages',                   ListPagesApiCall::MESSAGE,  'apiCall.pages.list')
            ->addApiCall('GET',    '/page/{uuid:[0-9a-z\-]+}', GetPageApiCall::MESSAGE,    'apiCall.pages.get')
            ->addApiCall('PUT',    '/page/{uuid:[0-9a-z\-]+}', UpdatePageApiCall::MESSAGE, 'apiCall.pages.update')
            ->addApiCall('DELETE', '/page/{uuid:[0-9a-z\-]+}', DeletePageApiCall::MESSAGE, 'apiCall.pages.delete');
    }
}
