<?php

namespace spec\Spot\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;

/** @mixin  \Spot\Api\Application */
class ApplicationSpec extends ObjectBehavior
{
    /** @var  \Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface $requestParser */
    private $requestParser;

    /** @var  \Spot\Api\Request\RequestBusInterface $requestBus */
    private $requestBus;

    /** @var  \Spot\Api\Response\ResponseBusInterface $responseBus */
    private $responseBus;

    /** @var  \Psr\Log\LoggerInterface $logger */
    private $logger;

    /**
     * @param  \Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface $requestParser
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
        $this->requestBus->execute($request)
            ->willReturn($response);
        $this->responseBus->execute($response)
            ->willReturn($httpResponse);

        $this->execute($httpRequest)->shouldReturn($httpResponse);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     * @param  \Psr\Http\Message\ResponseInterface $httpResponse
     * @param  \Spot\Api\Request\RequestException $exception
     */
    public function it_shouldBeAbleToHandleBadRequest($httpRequest, $request, $response, $httpResponse, $exception)
    {
        $exception->getRequestObject()->willReturn($request);

        $this->requestParser->parseHttpRequest($httpRequest, [])
            ->willThrow($exception->getWrappedObject());
        $this->requestBus->execute($request)
            ->willReturn($response);
        $this->responseBus->execute($response)
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
        $request->getAcceptContentType()->willReturn('application/vnd.api+json');
        $responseException = new ResponseException(
            'Reasons',
            new ServerErrorResponse([], $request->getWrappedObject())
        );

        $this->requestParser->parseHttpRequest($httpRequest, [])
            ->willReturn($request);
        $this->requestBus->execute($request)
            ->willThrow($responseException);
        $this->responseBus->execute($responseException->getResponseObject())
            ->willReturn($httpResponse);

        $this->execute($httpRequest)->shouldReturn($httpResponse);
    }
}
