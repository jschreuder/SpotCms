<?php

namespace spec\Spot\Api\Application;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Application;
use Spot\Api\Application\Request\RequestException;
use Spot\Api\Application\Response\ResponseException;

/** @mixin  \Spot\Api\Application\Application */
class ApplicationSpec extends ObjectBehavior
{
    /** @var  \Spot\Api\Application\Request\HttpRequestParserInterface $requestParser */
    private $requestParser;

    /** @var  \Spot\Api\Application\Request\RequestBusInterface $requestBus */
    private $requestBus;

    /** @var  \Spot\Api\Application\Response\ResponseBusInterface $responseBus */
    private $responseBus;

    /** @var  \Psr\Log\LoggerInterface $logger */
    private $logger;

    /**
     * @param  \Spot\Api\Application\Request\HttpRequestParserInterface $requestParser
     * @param  \Spot\Api\Application\Request\RequestBusInterface $requestBus
     * @param  \Spot\Api\Application\Response\ResponseBusInterface $responseBus
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
     * @param  \Spot\Api\Application\Request\Message\RequestInterface $request
     * @param  \Spot\Api\Application\Response\Message\ResponseInterface $response
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
     * @param  \Spot\Api\Application\Response\Message\ResponseInterface $response
     * @param  \Psr\Http\Message\ResponseInterface $httpResponse
     */
    public function it_shouldBeAbleToHandleBadRequest($httpRequest, $response, $httpResponse)
    {
        $badRequestException = new RequestException();

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
     * @param  \Spot\Api\Application\Request\Message\RequestInterface $request
     * @param  \Psr\Http\Message\ResponseInterface $httpResponse
     */
    public function it_shouldBeAbleToHandleResponseExceptions($httpRequest, $request, $httpResponse)
    {
        $responseException = new ResponseException();

        $this->requestParser->parseHttpRequest($httpRequest, [])
            ->willReturn($request);
        $this->requestBus->execute($httpRequest, $request)
            ->willThrow($responseException);
        $this->responseBus->execute($httpRequest, $responseException->getResponseObject())
            ->willReturn($httpResponse);

        $this->execute($httpRequest)->shouldReturn($httpResponse);
    }
}
