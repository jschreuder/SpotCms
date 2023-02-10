<?php declare(strict_types = 1);

namespace Spot;

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\ApplicationStackInterface;
use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\UrlGeneratorInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\Http\JsonRequestBodyParser;
use Spot\Auth\Middleware\AuthMiddleware;
use Spot\DataModel\Repository\ObjectRepository;

trait DefaultServiceProvider
{
    public function getApp(): ApplicationStackInterface
    {
        return new ApplicationStack(
            new ControllerRunner(),
            new RequestValidatorMiddleware(
                function (ServerRequestInterface $request, ValidationFailedException $exception) {
                    return new JsonApiErrorResponse($exception->getValidationErrors(), 400);
                }),
            new RequestFilterMiddleware(),
            new RoutingMiddleware($this->getRouter(), $this->get404Handler()),
            new AuthMiddleware($this->getTokenService(), []),
            new JsonRequestBodyParser(),
            new ErrorHandlerMiddleware($this->getLogger(), $this->get500Handler())
        );
    }
    public function getLogger(): LoggerInterface
    {
        $logger = new \Monolog\Logger($this->config('logger.name'));
        $logger->pushHandler((new \Monolog\Handler\StreamHandler(
            $this->config('logger.path'),
            $this->config('logger.level')
        ))->setFormatter(new \Monolog\Formatter\LineFormatter()));
        return $logger;
    }

    public function getRouter(): RouterInterface
    {
        return new SymfonyRouter($this->config('site.url'));
    }

    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->getRouter()->getGenerator();
    }

    public function get404Handler(): ControllerInterface
    {
        return CallableController::fromCallable(
            function (ServerRequestInterface $request) : ResponseInterface {
                return new JsonApiErrorResponse(
                    [
                        'ENDPOINT_NOT_FOUND' => 'Endpoint not found: ' .
                            $request->getMethod() . ' ' . $request->getUri()->getPath(),
                    ],
                    404
                );
            }
        );
    }

    public function get500Handler(): ControllerInterface
    {
        return CallableController::fromCallable(
            function () : ResponseInterface {
                return new JsonApiErrorResponse(['SYSTEM_ERROR' => 'System Error'], 500);
            }
        );
    }

    public function getDatabase(): PDO
    {
        return new PDO(
            $this->config('db.dsn') . ';dbname=' . $this->config('db.dbname'),
            $this->config('db.user'),
            $this->config('db.pass'),
            [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]
        );
    }

    public function getObjectRepository(): ObjectRepository
    {
        return new ObjectRepository($this->getDatabase());
    }
}
