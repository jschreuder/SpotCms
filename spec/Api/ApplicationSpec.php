<?php

namespace spec\Spot\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application;
use Spot\Api\Request\RequestException;
use Spot\Api\Response\ResponseException;

/** @mixin  \Spot\Api\Application */
class ApplicationSpec extends ObjectBehavior
{
    /** @var  \Spot\Api\Request\HttpRequestParserInterface $requestParser */
    private $requestParser;

    /** @var  \Spot\Api\Request\RequestBusInterface $requestBus */
    private $requestBus;

    /** @var  \Spot\Api\Response\ResponseBusInterface $responseBus */
    private $responseBus;

    /** @var  \Psr\Log\LoggerInterface $logger */
    private $logger;

    /**
     * @param  \Spot\Api\Request\HttpRequestParserInterface $requestParser
     * @param  \Spot\Api\Request\RequestBusInterface $requestBus
     * @param  \Spot\Api\Response\ResponseBusInterface $responseBus
     * @param  \Psr\Log\LoggerInterface $logger
     */
    public function let($requestParser, $requestBus, $responseBus, $logger)
    {
        $this->requestParser = $requestParser;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;
        $this->logger = $logger;
        $this->beConstructedWith($requestParser, $requestBus, $responseBus, $logger);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(Application::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     * @param  \Psr\Http\Message\ResponseInterface $httpResponse
     */
    public function it_shouldBeAbleToSuccessfullyExecute($httpRequest, $request, $response, $httpResponse)
    {
        $this->requestParser->parseHttpRequest($httpRequest, [])
            ->willReturn($request);
        $this->requestBus->execute($httpRequest, $request)
            ->willReturn($response);
        $this->responseBus->execute($httpRequest, $response)
            ->willReturn($httpResponse);

        $this->execute($httpRequest)->shouldReturn($httpResponse);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     * @param  \Psr\Http\Message\ResponseInterface $httpResponse
     */
    public function it_shouldBeAbleToHandleBadRequest($httpRequest, $response, $httpResponse)
    {
        $badRequestException = new RequestException('Reasons');

        $this->requestParser->parseHttpRequest($httpRequest, [])
            ->willThrow($badRequestException);
        $this->requestBus->execute($httpRequest, $badRequestException->getRequestObject())
            ->willReturn($response);
        $this->responseBus->execute($httpRequest, $response)
            ->willReturn($httpResponse);

        $this->execute($httpRequest)->shouldReturn($httpResponse);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Psr\Http\Message\ResponseInterface $httpResponse
     */
    public function it_shouldBeAbleToHandleResponseExceptions($httpRequest, $request, $httpResponse)
    {
        $responseException = new ResponseException('Reasons');

        $this->requestParser->parseHttpRequest($httpRequest, [])
            ->willReturn($request);
        $this->requestBus->execute($httpRequest, $request)
            ->willThrow($responseException);
        $this->responseBus->execute($httpRequest, $responseException->getResponseObject())
            ->willReturn($httpResponse);

        $this->execute($httpRequest)->shouldReturn($httpResponse);
    }
}
