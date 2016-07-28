<?php declare(strict_types = 1);

namespace Spot\Auth;

use Pimple\Container;
use Spot\Api\ApplicationServiceProvider;
use Spot\Api\Handler\ErrorHandler;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\ServiceProvider\RoutingProviderInterface;
use Spot\Auth\Exception\LoginFailedException;
use Spot\Auth\Handler\LoginHandler;
use Spot\Auth\Handler\LogoutHandler;
use Spot\Auth\Handler\RefreshTokenHandler;
use Spot\Auth\Middleware\HttpRequestParserAuthMiddleware;
use Spot\Auth\Repository\TokenRepository;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Service\AuthenticationService;
use Spot\Auth\Service\TokenService;

class AuthServiceProvider implements
    RoutingProviderInterface
{
    /** @var  string */
    private $uriSegment;

    public function __construct(string $uriSegment)
    {
        $this->uriSegment = $uriSegment;
    }

    public function init(Container $container)
    {
        $container['repository.tokens'] = function (Container $container) {
            return new TokenRepository($container['db']);
        };

        $container['repository.users'] = function (Container $container) {
            return new UserRepository($container['db']);
        };

        $container['service.tokens'] = function (Container $container) {
            return new TokenService($container['repository.tokens'], 3600 * 24 * 1);
        };

        $container['service.authentication'] = function (Container $container) {
            return new AuthenticationService($container['repository.users'], $container['service.tokens']);
        };

        $container->extend('app.httpRequestParser',
            function (HttpRequestParserInterface $httpRequestParser, Container $container) {
                return new HttpRequestParserAuthMiddleware(
                    $httpRequestParser,
                    $container['service.tokens'],
                    $container['service.authentication'],
                    []
                );
            }
        );
    }

    public function registerRouting(Container $container, ApplicationServiceProvider $builder)
    {
        $container['handler.login'] = function (Container $container) {
            return new LoginHandler($container['service.authentication']);
        };
        $container['handler.refreshToken'] = function (Container $container) {
            return new RefreshTokenHandler($container['service.tokens']);
        };
        $container['handler.logout'] = function (Container $container) {
            return new LogoutHandler($container['service.tokens']);
        };

        // ErrorHandlers
        $container['error.invalidCredentials'] = function () {
            return new ErrorHandler(LoginFailedException::ERROR_INVALID_CREDENTIALS, 400);
        };
        $container['error.invalidEmailAddress'] = function () {
            return new ErrorHandler(LoginFailedException::ERROR_INVALID_EMAIL_ADDRESS, 400);
        };
        $container['error.invalidToken'] = function () {
            return new ErrorHandler(LoginFailedException::ERROR_INVALID_TOKEN, 401);
        };
        $container['error.systemError'] = function () {
            return new ErrorHandler(LoginFailedException::ERROR_SYSTEM_ERROR, 500);
        };
        $builder->addGenerator(
            LoginFailedException::ERROR_INVALID_CREDENTIALS,
            self::JSON_API_CT,
            'error.invalidCredentials'
        );
        $builder->addGenerator(
            LoginFailedException::ERROR_INVALID_EMAIL_ADDRESS,
            self::JSON_API_CT,
            'error.invalidEmailAddress'
        );
        $builder->addGenerator(
            LoginFailedException::ERROR_INVALID_TOKEN,
            self::JSON_API_CT,
            'error.invalidToken'
        );
        $builder->addGenerator(
            LoginFailedException::ERROR_SYSTEM_ERROR,
            self::JSON_API_CT,
            'error.systemError'
        );

        // Configure ApiBuilder to use Handlers & Response Generators
        $builder
            ->addParser('POST', $this->uriSegment . '/login', 'handler.login')
            ->addExecutor(LoginHandler::MESSAGE, 'handler.login')
            ->addGenerator(LoginHandler::MESSAGE, self::JSON_API_CT, 'handler.login');
        $builder
            ->addParser('POST', $this->uriSegment . '/token/refresh', 'handler.refreshToken')
            ->addExecutor(RefreshTokenHandler::MESSAGE, 'handler.refreshToken')
            ->addGenerator(RefreshTokenHandler::MESSAGE, self::JSON_API_CT, 'handler.refreshToken');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/logout', 'handler.logout')
            ->addExecutor(LogoutHandler::MESSAGE, 'handler.logout')
            ->addGenerator(LogoutHandler::MESSAGE, self::JSON_API_CT, 'handler.logout');
    }
}
