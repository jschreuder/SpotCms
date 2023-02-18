<?php declare(strict_types = 1);

namespace Spot\Auth;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Spot\Auth\Controller\LoginController;
use Spot\Auth\Controller\LogoutController;
use Spot\Auth\Controller\RefreshTokenController;

class AuthRoutingProvider implements RoutingProviderInterface
{
    public function __construct(
        private string $uriSegment,
        private AuthServiceProviderInterface $container
    )
    {
    }

    public function registerRoutes(RouterInterface $router): void
    {
        $router->post('login', $this->uriSegment . '/login', function () {
            return new LoginController($this->container->getAuthenticationService());
        });
        $router->post('refreshToken', $this->uriSegment . '/token/refresh', function () {
            return new RefreshTokenController($this->container->getTokenService());
        });
        $router->delete('logout', $this->uriSegment . '/logout', function () {
            return new LogoutController($this->container->getTokenService());
        });
    }
}
