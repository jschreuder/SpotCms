<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request;

use FastRoute\Dispatcher as Router;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Cms\Application\Request\Message\NotFoundRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Request\Message\ServerErrorRequest;
use Spot\Cms\Common\LoggableTrait;

class HttpRequestParserRouter implements HttpRequestParserInterface
{
    use LoggableTrait;

    /** @var  RouteCollector */
    private $routeCollector;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(RouteCollector $routeCollector, LoggerInterface $logger)
    {
        $this->routeCollector = $routeCollector;
        $this->logger = $logger;
    }

    public function addRoute(string $method, string $path, HttpRequestParserInterface $httpRequestParser) : self
    {
        $this->routeCollector->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    private function getRouter() : Router
    {
        return new GroupCountBasedDispatcher($this->routeCollector->getData());
    }

    /** {@inheritdoc} */
    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $method = $httpRequest->getMethod();
        $path = $httpRequest->getUri()->getPath();
        $routeInfo = $this->getRouter()->dispatch($method, $path);
        try {
            switch ($routeInfo[0]) {
                case Router::NOT_FOUND:
                case Router::METHOD_NOT_ALLOWED:
                    $this->log(LogLevel::INFO, 'No route found for ' . $method . ' ' . $path);
                    $request = new NotFoundRequest();
                    break;
                case Router::FOUND:
                    $parser = $routeInfo[1];
                    if (!$parser instanceof HttpRequestParserInterface) {
                        throw new \RuntimeException('No RequestParser configured for ' . $method . ' ' . $path);
                    }
                    $request = $parser->parseHttpRequest($httpRequest, array_merge($attributes, $routeInfo[2]));
                    $this->log(LogLevel::INFO, 'Found route found for ' . $method . ' ' . $path);
                    break;
                default:
                    throw new \RuntimeException('Routing errored for ' . $method . ' ' . $path);
            }
        } catch (\Exception $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            $request = new ServerErrorRequest();
        }

        if (!$request instanceof RequestInterface) {
            $this->log(LogLevel::ERROR, 'HttpRequestParser did not result in a Request message.');
            return new ServerErrorRequest();
        }
        return $request;
    }
}
