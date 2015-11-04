<?php

namespace Spot\Cms\Application;

use Psr\Http\Message\ServerRequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface;
use Spot\Cms\Application\Request\HttpRequestParserInterface;
use Spot\Cms\Application\Request\RequestBusInterface;
use Spot\Cms\Application\Request\RequestException;
use Spot\Cms\Application\Response\ResponseBusInterface;
use Spot\Cms\Application\Response\ResponseException;

class Application
{
    /** @var  HttpRequestParserInterface */
    private $requestParser;

    /** @var  RequestBusInterface */
    private $requestBus;

    /** @var  ResponseBusInterface */
    private $responseBus;

    public function __construct(
        HttpRequestParserInterface $requestParser,
        RequestBusInterface $requestBus,
        ResponseBusInterface $responseBus
    ) {
        $this->requestParser = $requestParser;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;
    }

    /**
     * @param   HttpRequest $httpRequest
     * @return  ResponseInterface
     */
    public function execute(HttpRequest $httpRequest)
    {
        try {
            $requestMessage = $this->requestParser->parse($httpRequest);
            $requestMessage->validate();
        } catch (RequestException $requestException) {
            $requestMessage = $requestException->getRequestObject();
        }

        try {
            $responseMessage = $this->requestBus->execute($httpRequest, $requestMessage);
        } catch (ResponseException $responseException) {
            $responseMessage = $responseException->getResponseObject();
        }

        return $this->responseBus->execute($httpRequest, $responseMessage);
    }
}
