<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use Spot\Api\Request\HttpRequestParserFactoryInterface;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Api\Request\HttpRequestParserRouter;
use Spot\Api\Request\RequestBus;
use Spot\Api\Request\RequestBusFactoryInterface;
use Spot\Api\Request\RequestBusInterface;
use Spot\Api\Response\ResponseBus;
use Spot\Api\Response\ResponseBusFactoryInterface;
use Spot\Api\Response\ResponseBusInterface;

class ApiBuilder implements
    HttpRequestParserFactoryInterface,
    RequestBusFactoryInterface,
    ResponseBusFactoryInterface
{
    /** @var  HttpRequestParserRouter */
    private $router;

    /** @var  RouteCollector */
    private $routeCollector;

    /** @var  RequestBus */
    private $requestBus;

    /** @var  ResponseBus */
    private $responseBus;

    public function __construct(
        HttpRequestParserRouter $router,
        RouteCollector $routeCollector,
        RequestBus $requestBus,
        ResponseBus $responseBus
    ) {
        $this->router = $router;
        $this->routeCollector = $routeCollector;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;
    }

    public function addParser(string $method, string $path, string $httpRequestParser) : self
    {
        $this->routeCollector->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    public function addRequestExecutor(string $requestName, string $executor) : self
    {
        $this->requestBus->setExecutor($requestName, $executor);
        return $this;
    }

    public function addResponseGenerator(string $responseName, string $generator) : self
    {
        $this->responseBus->setGenerator($responseName, $generator);
        return $this;
    }

    /** {@inheritdoc} */
    public function getHttpRequestParser() : HttpRequestParserInterface
    {
        return $this->router->setRouter(new GroupCountBasedDispatcher($this->routeCollector->getData()));
    }

    /** {@inheritdoc} */
    public function getRequestBus() : RequestBusInterface
    {
        return $this->requestBus;
    }

    /** {@inheritdoc} */
    public function getResponseBus() : ResponseBusInterface
    {
        return $this->responseBus;
    }
}
