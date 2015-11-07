<?php declare(strict_types=1);

namespace Spot\Api\Application\Request;

use FastRoute\Dispatcher as Router;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use Pimple\Container;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Application\Request\Message\NotFoundRequest;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Request\Message\ServerErrorRequest;
use Spot\Api\Common\LoggableTrait;

class HttpRequestParserRouter implements HttpRequestParserInterface
{
    use LoggableTrait;

    /** @var  Container */
    private $container;

    /** @var  RouteCollector */
    private $routeCollector;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(Container $container, RouteCollector $routeCollector, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->routeCollector = $routeCollector;
        $this->logger = $logger;
    }

    public function addRoute(string $method, string $path, $httpRequestParser) : self
    {
        $this->routeCollector->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    private function getRouter() : Router
    {
        return new GroupCountBasedDispatcher($this->routeCollector->getData());
    }

    private function getHttpRequestParser($name) : HttpRequestParserInterface
    {
        $httpRequestParser = $this->container[$name];
        if (!$httpRequestParser instanceof HttpRequestParserInterface) {
            throw new \RuntimeException('HttpRequestParser must implement HttpRequestParserInterface.');
        }
        return $httpRequestParser;
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
                    $parser = $this->getHttpRequestParser($routeInfo[1]);
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
