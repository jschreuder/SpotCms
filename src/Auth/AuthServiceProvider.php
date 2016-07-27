<?php declare(strict_types = 1);

namespace Spot\Auth;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Api\ApplicationServiceProvider;
use Spot\Api\ServiceProvider\RepositoryProviderInterface;
use Spot\Api\ServiceProvider\RoutingProviderInterface;
use Spot\Auth\Handler\LoginHandler;
use Spot\Auth\Repository\TokenRepository;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Service\AuthenticationService;
use Spot\Auth\Service\TokenService;

class AuthServiceProvider implements
    ServiceProviderInterface,
    RepositoryProviderInterface,
    RoutingProviderInterface
{
    /** @var  string */
    private $uriSegment;

    public function __construct(string $uriSegment)
    {
        $this->uriSegment = $uriSegment;
    }

    public function register(Container $container)
    {
        $container['service.tokens'] = function (Container $container) {
            return new TokenService($container['repository.tokens'], 3600 * 24 * 1);
        };

        $container['service.authentication'] = function (Container $container) {
            return new AuthenticationService($container['repository.users'], $container['service.tokens']);
        };
    }

    public function registerRepositories(Container $container)
    {
        $container['repository.tokens'] = function (Container $container) {
            return new TokenRepository($container['db']);
        };

        $container['repository.users'] = function (Container $container) {
            return new UserRepository($container['db']);
        };
    }

    public function registerRouting(Container $container, ApplicationServiceProvider $builder)
    {
        $container['handler.login'] = function (Container $container) {
            return new LoginHandler($container['service.authentication']);
        };

        // Configure ApiBuilder to use Handlers & Response Generators4
        $builder
            ->addParser('POST', $this->uriSegment . '/login', 'handler.login')
            ->addExecutor(LoginHandler::MESSAGE, 'handler.login')
            ->addGenerator(LoginHandler::MESSAGE, self::JSON_API_CT, 'handler.login')
            ->addGenerator(LoginHandler::MESSAGE . '.error', self::JSON_API_CT, 'handler.login');
    }
}
