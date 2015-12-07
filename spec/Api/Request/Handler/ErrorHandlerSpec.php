<?php

namespace spec\Spot\Api\Request\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\Request\Handler\ErrorHandler;
use Spot\Api\Response\Message\ArrayResponse;

/** @mixin  ErrorHandler */
class ErrorHandlerSpec extends ObjectBehavior
{
    /** @var  string */
    private $name = 'test.nest';

    /** @var  int */
    private $statusCode = 418;

    /** @var  string */
    private $message = 'Test a nest on a vest to rest.';

    public function let()
    {
        $this->beConstructedWith($this->name, $this->statusCode, $this->message);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(ErrorHandler::class);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     */
    public function it_canExecuteARequest($request, $httpRequest)
    {
        $response = $this->executeRequest($request, $httpRequest);
        $response->shouldHaveType(ArrayResponse::class);
        $response->getResponseName()->shouldReturn($this->name);
        $response->getData()->shouldReturn([]);
    }

    /**
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     */
    public function it_canGenerateAResponse($response, $httpRequest)
    {
        $httpResponse = $this->generateResponse($response, $httpRequest);
        $httpResponse->shouldHaveType(JsonApiErrorResponse::class);
    }
}
