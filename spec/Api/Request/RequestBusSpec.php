<?php

namespace spec\Spot\Api\Request;

use PhpSpec\ObjectBehavior;
use Pimple\Container;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\RequestBus;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\ResponseException;

/** @mixin  \Spot\Api\Request\RequestBus */
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
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_canExecuteSuccessfully($request)
    {
        $requestName = 'request.name';
        $response = new Response($requestName, []);
        $executorName = 'executor.test';
        $executor = new class($response) implements ExecutorInterface {
            private $response;
            public function __construct($response)
            {
                $this->response = $response;
            }
            public function executeRequest(RequestInterface $request) : ResponseInterface
            {
                return $this->response;
            }
        };
        $this->container[$executorName] = $executor;
        $this->setExecutor($requestName, $executorName)
            ->shouldReturn($this);

        $request->getRequestName()
            ->willReturn($requestName);

        $this->execute($request)
            ->shouldReturn($response);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_willErrorOnUnsupportedRequest($request)
    {
        $requestName = 'request.name';
        $request->getRequestName()
            ->willReturn($requestName);

        $this->shouldThrow(ResponseException::class)->duringExecute($request);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_willErrorOnUndefinedExecutor($request)
    {
        $requestName = 'request.name';
        $executorName = 'executor.test';

        $this->setExecutor($requestName, $executorName)
            ->shouldReturn($this);

        $request->getRequestName()
            ->willReturn($requestName);

        $this->shouldThrow(ResponseException::class)->duringExecute($request);
    }
}
