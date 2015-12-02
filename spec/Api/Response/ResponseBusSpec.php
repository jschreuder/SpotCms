<?php

namespace spec\Spot\Api\Response;

use PhpSpec\ObjectBehavior;
use Pimple\Container;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\ResponseBus;
use Zend\Diactoros\Response;

/** @mixin  \Spot\Api\Response\ResponseBus */
class ResponseBusSpec extends ObjectBehavior
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
        $this->shouldHaveType(ResponseBus::class);
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     */
    public function it_canExecuteSuccessfully($httpRequest, $response)
    {
        $responseName = 'response.name';
        $httpResponse = new Response();
        $generatorName = 'generator.test';
        $generator = new class($httpResponse) implements GeneratorInterface {
            private $httpResponse;
            public function __construct(HttpResponse $httpResponse)
            {
                $this->httpResponse = $httpResponse;
            }
            public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
            {
                return $this->httpResponse;
            }
        };
        $this->container[$generatorName] = $generator;
        $this->setGenerator($responseName, $generatorName)
            ->shouldReturn($this);

        $response->getResponseName()
            ->willReturn($responseName);

        $this->execute($httpRequest, $response)
            ->shouldReturn($httpResponse);
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     */
    public function it_willErrorOnUnsupportedRequest($httpRequest, $response)
    {
        $responseName = 'request.name';
        $response->getResponseName()
            ->willReturn($responseName);

        $this->execute($httpRequest, $response)
            ->shouldReturnAnInstanceOf(HttpResponse::class);
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     */
    public function it_willErrorOnUndefinedExecutor($httpRequest, $response)
    {
        $responseName = 'request.name';
        $generatorName = 'executor.test';

        $this->setGenerator($responseName, $generatorName)
            ->shouldReturn($this);

        $response->getResponseName()
            ->willReturn($responseName);

        $this->execute($httpRequest, $response)
            ->shouldReturnAnInstanceOf(HttpResponse::class);
    }
}
