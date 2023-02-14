<?php declare(strict_types=1);

namespace Spot\Application\Middleware;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RouterInterface $router,
        private ControllerInterface $fallbackController
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $routeMatch = $this->router->parseRequest($request);

        if ($routeMatch->isMatch()) {
            // Register Controller to the request object
            $request = $request->withAttribute('controller', $routeMatch->getController());

            // Merge routing attributes with query parameters
            $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeMatch->getAttributes()));
        } else {
            $request = $request->withAttribute('controller', $this->fallbackController);
        }

        return $requestHandler->handle($request);
    }
}
