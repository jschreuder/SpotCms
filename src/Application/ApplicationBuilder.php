<?php declare(strict_types=1);

namespace Spot\Api\Application;

use Spot\Api\Application\Request\HttpRequestParserInterface;
use Spot\Api\Application\Request\HttpRequestParserRouter;
use Spot\Api\Application\Request\RequestBus;
use Spot\Api\Application\Request\RequestBusInterface;
use Spot\Api\Application\Response\ResponseBus;
use Spot\Api\Application\Response\ResponseBusInterface;

class ApplicationBuilder implements ApplicationBuilderInterface
{
    /** @var  HttpRequestParserRouter */
    private $router;

    /** @var  RequestBus */
    private $requestBus;

    /** @var  ResponseBus */
    private $responseBus;

    public function __construct(
        HttpRequestParserRouter $router,
        RequestBus $requestBus,
        ResponseBus $responseBus
    ) {
        $this->router = $router;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;
    }

    /** {@inheritdoc} */
    public function addParser(string $method, string $path, $httpRequestParser) : self
    {
        $this->router->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    /** {@inheritdoc} */
    public function addRequestExecutor(string $requestName, $executor) : self
    {
        $this->requestBus->setExecutor($requestName, $executor);
        return $this;
    }

    /** {@inheritdoc} */
    public function addResponseGenerator(string $responseName, $generator) : self
    {
        $this->responseBus->setGenerator($responseName, $generator);
        return $this;
    }

    public function addApiCall(string $method, string $path, string $name, $apiCall) : self
    {
        return $this->addParser($method, $path, $apiCall)
            ->addRequestExecutor($name, $apiCall)
            ->addResponseGenerator($name, $apiCall);
    }

    public function getHttpRequestParser() : HttpRequestParserInterface
    {
        return $this->router;
    }

    public function getRequestBus() : RequestBusInterface
    {
        return $this->requestBus;
    }

    public function getResponseBus() : ResponseBusInterface
    {
        return $this->responseBus;
    }
}
