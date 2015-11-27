<?php declare(strict_types=1);

namespace Spot\Api\Application;

use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use Spot\Api\Application\Request\HttpRequestParserFactoryInterface;
use Spot\Api\Application\Request\HttpRequestParserInterface;
use Spot\Api\Application\Request\HttpRequestParserRouter;
use Spot\Api\Application\Request\RequestBus;
use Spot\Api\Application\Request\RequestBusFactoryInterface;
use Spot\Api\Application\Request\RequestBusInterface;
use Spot\Api\Application\Response\ResponseBus;
use Spot\Api\Application\Response\ResponseBusFactoryInterface;
use Spot\Api\Application\Response\ResponseBusInterface;

class ApplicationBuilder implements
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

    public function addParser(\string $method, \string $path, \string $httpRequestParser) : self
    {
        $this->routeCollector->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    public function addRequestExecutor(\string $requestName, \string $executor) : self
    {
        $this->requestBus->setExecutor($requestName, $executor);
        return $this;
    }

    public function addResponseGenerator(\string $responseName, \string $generator) : self
    {
        $this->responseBus->setGenerator($responseName, $generator);
        return $this;
    }

    public function addApiCall(\string $method, \string $path, \string $name, \string $apiCall) : self
    {
        return $this->addParser($method, $path, $apiCall)
            ->addRequestExecutor($name, $apiCall)
            ->addResponseGenerator($name, $apiCall);
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
