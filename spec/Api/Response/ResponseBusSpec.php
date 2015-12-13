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
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     */
    public function it_canExecuteSuccessfully($response)
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
            public function generateResponse(ResponseInterface $response) : HttpResponse
            {
                return $this->httpResponse;
            }
        };
        $this->container[$generatorName] = $generator;
        $this->setGenerator($responseName, 'application/vnd.api+json', $generatorName)
            ->shouldReturn($this);

        $response->getResponseName()
            ->willReturn($responseName);
        $response->getContentType()
            ->willReturn('application/vnd.api+json, application/json;q=0.5');

        $this->execute($response)
            ->shouldReturn($httpResponse);
    }

    /**
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     */
    public function it_willErrorOnUnsupportedRequest($response)
    {
        $responseName = 'request.name';
        $response->getResponseName()
            ->willReturn($responseName);
        $response->getContentType()
            ->willReturn('application/vnd.api+json, application/json;q=0.5');

        $this->execute($response)
            ->shouldReturnAnInstanceOf(HttpResponse::class);
    }

    /**
     * @param  \Spot\Api\Response\Message\ResponseInterface $response
     */
    public function it_willErrorOnUndefinedExecutor($response)
    {
        $responseName = 'request.name';
        $generatorName = 'executor.test';

        $this->setGenerator($responseName, 'application/vnd.api+json', $generatorName)
            ->shouldReturn($this);

        $response->getResponseName()
            ->willReturn($responseName);
        $response->getContentType()
            ->willReturn('application/json;q=0.5, text/html,*/*;q=0.3');

        $this->execute($response)
            ->shouldReturnAnInstanceOf(HttpResponse::class);
    }
}
