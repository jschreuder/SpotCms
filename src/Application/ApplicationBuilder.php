<?php declare(strict_types=1);

namespace Spot\Cms\Application;

use Spot\Cms\Application\Request\Executor\ExecutorInterface;
use Spot\Cms\Application\Request\HttpRequestParserInterface;
use Spot\Cms\Application\Request\HttpRequestParserRouter;
use Spot\Cms\Application\Request\RequestBus;
use Spot\Cms\Application\Request\RequestBusInterface;
use Spot\Cms\Application\Response\Generator\GeneratorInterface;
use Spot\Cms\Application\Response\ResponseBus;
use Spot\Cms\Application\Response\ResponseBusInterface;

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
    public function addParser(string $method, string $path, HttpRequestParserInterface $httpRequestParser) : self
    {
        $this->router->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    /** {@inheritdoc} */
    public function addRequestExecutor(string $requestName, ExecutorInterface $executor) : self
    {
        $this->requestBus->setExecutor($requestName, $executor);
        return $this;
    }

    /** {@inheritdoc} */
    public function addResponseGenerator(string $responseName, GeneratorInterface $generator) : self
    {
        $this->responseBus->setGenerator($responseName, $generator);
        return $this;
    }

    public function addApiCall(string $method, string $path, string $name, ApiCallInterface $apiCall) : self
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
