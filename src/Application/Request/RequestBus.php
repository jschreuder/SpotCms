<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Cms\Application\Request\Message\NotFound;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Response\Message\ResponseInterface;
use Spot\Cms\Application\Response\Message\ServerError;
use Spot\Cms\Application\Response\ResponseException;

class RequestBus implements RequestBusInterface
{
    /** @var  callable[] */
    private $executors = [];

    /** @var  LoggerInterface */
    private $logger;

    /**
     * @param  callable[] $executors
     * @param  LoggerInterface $logger
     */
    public function __construct(array $executors = [], LoggerInterface $logger)
    {
        foreach ($executors as $name => $executor) {
            $this->setExecutor($name, $executor);
        }
        $this->logger = $logger;
    }

    /**
     * @param   string $name
     * @param   callable $executor
     * @return  self
     */
    public function setExecutor($name, callable $executor)
    {
        $this->executors[strval($name)] = $executor;
        return $this;
    }

    /**
     * @param   RequestInterface $request
     * @return  callable
     */
    protected function getExecutor(RequestInterface $request)
    {
        return $this->executors[$request->getName()];
    }

    /** {@inheritdoc} */
    public function supports(RequestInterface $request)
    {
        return array_key_exists($request->getName(), $this->executors);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage)
    {
        if (!$this->supports($requestMessage)) {
            $this->log('Unsupported request: ' . $requestMessage->getName(), LogLevel::WARNING);
            throw new ResponseException(new NotFound(), 404);
        }

        $requestExecutor = $this->getExecutor($requestMessage);
        $responseMessage = $requestExecutor($httpRequest, $requestMessage);

        if (!$responseMessage instanceof ResponseInterface) {
            $this->log('Executor for ' . $requestMessage->getName() . ' did not return Response.', LogLevel::ERROR);
            throw new ResponseException(new ServerError(), 500);
        }

        return $responseMessage;
    }

    /**
     * @param   string $message
     * @param   string $level
     * @return  void
     */
    protected function log($message, $level)
    {
        $this->logger->log($level, '[RequestBus] ' . $message);
    }
}
