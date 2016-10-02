<?php declare(strict_types = 1);

namespace Spot\Auth;

use jschreuder\Middle\ApplicationStackInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Auth\Controller\LoginController;
use Spot\Auth\Controller\LogoutController;
use Spot\Auth\Controller\RefreshTokenController;
use Spot\Auth\Middleware\AuthMiddleware;
use Spot\Auth\Repository\TokenRepository;
use Spot\Auth\Repository\UserRepository;

class AuthServiceProvider implements ServiceProviderInterface, RoutingProviderInterface
{
    /** @var  Container */
    private $container;

    /** @var  string */
    private $uriSegment;

    public function __construct(Container $container, string $uriSegment)
    {
        $this->container = $container;
        $container->register($this);

        $this->uriSegment = $uriSegment;
    }

    public function register(Container $container)
    {
        $container['repository.tokens'] = function (Container $container) {
            return new TokenRepository($container['db']);
        };

        $container['repository.users'] = function (Container $container) {
            return new UserRepository($container['db'], $container['repository.objects']);
        };

        $container['service.tokens'] = function (Container $container) {
            return new TokenService($container['repository.tokens'], 3600 * 24 * 1);
        };

        $container['service.authentication'] = function (Container $container) {
            return new AuthenticationService(
                $container['repository.users'],
                $container['service.tokens'],
                $container['logger']
            );
        };
    }

    public function registerRoutes(RouterInterface $router)
    {
        $router->post('login', $this->uriSegment . '/login', function () {
            return new LoginController($this->container['service.authentication'], $this->container['logger']);
        });
        $router->post('refreshToken', $this->uriSegment . '/token/refresh', function () {
            return new RefreshTokenController($this->container['service.tokens'], $this->container['logger']);
        });
        $router->delete('logout', $this->uriSegment . '/logout', function () {
            return new LogoutController($this->container['service.tokens'], $this->container['logger']);
        });
    }
}
