<?php

namespace spec\Spot\Api\Request\HttpRequestParser;

use FastRoute\Dispatcher as Router;
use PhpSpec\ObjectBehavior;
use Pimple\Container;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\NotFoundRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\Message\ServerErrorRequest;

/** @mixin  \Spot\Api\Request\HttpRequestParser\HttpRequestParserRouter */
class HttpRequestParserRouterSpec extends ObjectBehavior
{
    /** @var  Container */
    private $container;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param   \Psr\Log\LoggerInterface $logger
     */
    public function let($logger)
    {
        $this->container = new Container();
        $this->logger = $logger;
        $this->beConstructedWith($this->container, $logger);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(\Spot\Api\Request\HttpRequestParser\HttpRequestParserRouter::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Psr\Http\Message\UriInterface $uri
     * @param  \FastRoute\Dispatcher $router
     */
    public function it_canParseSuccessfully($httpRequest, $uri, $router)
    {
        $method = 'GET';
        $path = '/life/universe/everything/';
        $requestName = 'forty.two';
        $request = new ArrayRequest($requestName, []);

        $httpRequest->getMethod()
            ->willReturn($method);
        $httpRequest->getUri()
            ->willReturn($uri);
        $uri->getPath()
            ->willReturn($path);

        $this->setRouter($router);
        $router->dispatch($method, $path)
            ->willReturn([Router::FOUND, $requestName, []]);

        $this->container[$requestName] = new class($request) implements HttpRequestParserInterface {
            private $request;
            public function __construct($request)
            {
                $this->request = $request;
            }
            public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
            {
                return $this->request;
            }
        };

        $this->parseHttpRequest($httpRequest, [])
            ->shouldReturn($request);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Psr\Http\Message\UriInterface $uri
     * @param  \FastRoute\Dispatcher $router
     */
    public function it_canHandleA404($httpRequest, $uri, $router)
    {
        $method = 'GET';
        $path = '/life/universe/nothing/';

        $httpRequest->getMethod()
            ->willReturn($method);
        $httpRequest->getUri()
            ->willReturn($uri);
        $uri->getPath()
            ->willReturn($path);

        $this->setRouter($router);
        $router->dispatch($method, $path)
            ->willReturn([Router::NOT_FOUND]);

        $this->parseHttpRequest($httpRequest, [])
            ->shouldReturnAnInstanceOf(NotFoundRequest::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Psr\Http\Message\UriInterface $uri
     * @param  \FastRoute\Dispatcher $router
     */
    public function it_canHandleAMethodUnsupported($httpRequest, $uri, $router)
    {
        $method = 'DELETE';
        $path = '/life/universe/everything/';

        $httpRequest->getMethod()
            ->willReturn($method);
        $httpRequest->getUri()
            ->willReturn($uri);
        $uri->getPath()
            ->willReturn($path);

        $this->setRouter($router);
        $router->dispatch($method, $path)
            ->willReturn([Router::METHOD_NOT_ALLOWED]);

        $this->parseHttpRequest($httpRequest, [])
            ->shouldReturnAnInstanceOf(NotFoundRequest::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Psr\Http\Message\UriInterface $uri
     * @param  \FastRoute\Dispatcher $router
     */
    public function it_errorsOnBadRoutingResult($httpRequest, $uri, $router)
    {
        $method = 'GET';
        $path = '/travel/without/towel';

        $httpRequest->getMethod()
            ->willReturn($method);
        $httpRequest->getUri()
            ->willReturn($uri);
        $uri->getPath()
            ->willReturn($path);

        $this->setRouter($router);
        $router->dispatch($method, $path)
            ->willReturn([-42]);

        $this->parseHttpRequest($httpRequest, [])
            ->shouldReturnAnInstanceOf(ServerErrorRequest::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Psr\Http\Message\UriInterface $uri
     * @param  \FastRoute\Dispatcher $router
     */
    public function it_errorsOnInvalidHttpRequestParser($httpRequest, $uri, $router)
    {
        $method = 'GET';
        $path = '/bureaucracy/form/processor/';
        $requestName = 'vogon';

        $httpRequest->getMethod()
            ->willReturn($method);
        $httpRequest->getUri()
            ->willReturn($uri);
        $uri->getPath()
            ->willReturn($path);

        $this->setRouter($router);
        $router->dispatch($method, $path)
            ->willReturn([Router::FOUND, $requestName, []]);

        $this->container[$requestName] = new \stdClass();

        $this->parseHttpRequest($httpRequest, [])
            ->shouldReturnAnInstanceOf(ServerErrorRequest::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Psr\Http\Message\UriInterface $uri
     * @param  \FastRoute\Dispatcher $router
     */
    public function it_errorsOnInvalidRequestObject($httpRequest, $uri, $router)
    {
        $method = 'POST';
        $path = '/submitted/just/once/';
        $requestName = 'vogon.error';

        $httpRequest->getMethod()
            ->willReturn($method);
        $httpRequest->getUri()
            ->willReturn($uri);
        $uri->getPath()
            ->willReturn($path);

        $this->setRouter($router);
        $router->dispatch($method, $path)
            ->willReturn([Router::FOUND, $requestName, []]);

        $this->container[$requestName] = new class implements \Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface {
            public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
            {
                return new \stdClass();
            }
        };

        $this->parseHttpRequest($httpRequest, [])
            ->shouldReturnAnInstanceOf(ServerErrorRequest::class);
    }
}