<?php declare(strict_types = 1);

namespace Spot;

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Controller\FilterValidationMiddleware;
use jschreuder\Middle\Controller\ValidationFailedException;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingMiddleware;
use jschreuder\Middle\Router\SymfonyRouter;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\Http\JsonRequestBodyParser;
use Spot\Auth\Middleware\AuthMiddleware;
use Spot\DataModel\Repository\ObjectRepository;

class DefaultServiceProvider implements ServiceProviderInterface
{
    /** {@inheritdoc} */
    public function register(Container $container)
    {
        $container['app'] = function (Container $container) {
            return new ApplicationStack([
                new ControllerRunner(),
                new FilterValidationMiddleware(
                    function (ServerRequestInterface $request, ValidationFailedException $exception) {
                        return new JsonApiErrorResponse($exception->getValidationErrors(), 400);
                    }
                ),
                new RoutingMiddleware(
                    $container['app.router'],
                    $container['app.error_handlers.404']
                ),
                new AuthMiddleware(
                    $container['service.tokens'],
                    $container['service.authentication'],
                    []
                ),
                new JsonRequestBodyParser(),
            ]);
        };

        $container['app.router'] = function () use ($container) {
            return new SymfonyRouter($container['site.url']);
        };

        $container['url_generator'] = function () use ($container) {
            /** @var  RouterInterface $router */
            $router = $container['app.router'];
            return $router->getGenerator();
        };

        $container['app.error_handlers.404'] = CallableController::fromCallable(
            function (ServerRequestInterface $request) use ($container) : ResponseInterface {
                return new JsonApiErrorResponse(
                    [
                        'ENDPOINT_NOT_FOUND' => 'Endpoint not found: ' .
                            $request->getMethod() . ' ' . $request->getUri()->getPath(),
                    ],
                    404
                );
            }
        );

        $container['app.error_handlers.500'] = CallableController::fromCallable(
            function () use ($container) : ResponseInterface {
                return new JsonApiErrorResponse(['SYSTEM_ERROR' => 'System Error'], 500);
            }
        );

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

        $container['repository.objects'] = function (Container $container) {
            return new ObjectRepository($container['db']);
        };
    }
}
