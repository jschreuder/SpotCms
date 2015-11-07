<?php

namespace spec\Spot\Api\Application\Request;

use PhpSpec\ObjectBehavior;
use Pimple\Container;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Api\Application\Request\Executor\ExecutorInterface;
use Spot\Api\Application\Request\RequestBus;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Response\Message\ArrayResponse;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Application\Response\ResponseException;

/** @mixin  \Spot\Api\Application\Request\RequestBus */
class RequestBusSpec extends ObjectBehavior
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
        $this->shouldHaveType(RequestBus::class);
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     * @param  \Spot\Api\Application\Request\Message\RequestInterface $request
     */
    public function it_canExecuteSuccessfully($httpRequest, $request)
    {
        $requestName = 'request.name';
        $response = new ArrayResponse($requestName, []);
        $executorName = 'executor.test';
        $executor = new class($response) implements ExecutorInterface {
            private $response;
            public function __construct($response)
            {
                $this->response = $response;
            }
            public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
            {
                return $this->response;
            }
        };
        $this->container[$executorName] = $executor;
        $this->setExecutor($requestName, $executorName)
            ->shouldReturn($this);

        $request->getRequestName()
            ->willReturn($requestName);

        $this->execute($httpRequest, $request)
            ->shouldReturn($response);
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     * @param  \Spot\Api\Application\Request\Message\RequestInterface $request
     */
    public function it_willErrorOnUnsupportedRequest($httpRequest, $request)
    {
        $requestName = 'request.name';
        $request->getRequestName()
            ->willReturn($requestName);

        $this->shouldThrow(ResponseException::class)->duringExecute($httpRequest, $request);
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     * @param  \Spot\Api\Application\Request\Message\RequestInterface $request
     */
    public function it_willErrorOnUndefinedExecutor($httpRequest, $request)
    {
        $requestName = 'request.name';
        $executorName = 'executor.test';

        $this->setExecutor($requestName, $executorName)
            ->shouldReturn($this);

        $request->getRequestName()
            ->willReturn($requestName);

        $this->shouldThrow(ResponseException::class)->duringExecute($httpRequest, $request);
    }
}
