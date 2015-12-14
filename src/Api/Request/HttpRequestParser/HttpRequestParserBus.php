<?php declare(strict_types = 1);

namespace Spot\Api\Request\HttpRequestParser;

use FastRoute\Dispatcher as Router;
use Pimple\Container;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\NotFoundRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\Message\ServerErrorRequest;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\RequestException;

class HttpRequestParserBus implements HttpRequestParserInterface
{
    use LoggableTrait;

    /** @var  Container */
    private $container;

    /** @var  Router */
    private $router;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(Container $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function setRouter(Router $router) : self
    {
        $this->router = $router;
        return $this;
    }

    private function getRouter() : Router
    {
        if (is_null($this->router)) {
            throw new \RuntimeException('Router must be provided to allow Request parsing.');
        }
        return $this->router;
    }

    private function getHttpRequestParser($name) : HttpRequestParserInterface
    {
        return $this->container[$name];
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
                    $request = new NotFoundRequest([], $httpRequest);
                    break;
                case Router::FOUND:
                    $parser = $this->getHttpRequestParser($routeInfo[1]);
                    $request = $parser->parseHttpRequest($httpRequest, array_merge($attributes, $routeInfo[2]));
                    $this->log(LogLevel::INFO, 'Found route found for ' . $method . ' ' . $path);
                    break;
                default:
                    throw new \RuntimeException('Routing errored for ' . $method . ' ' . $path);
            }
        } catch (RequestException $exception) {
            $request = $exception->getRequestObject();
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            $request = new ServerErrorRequest([], $httpRequest);
        }

        return $request;
    }
}
