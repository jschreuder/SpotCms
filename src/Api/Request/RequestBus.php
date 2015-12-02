<?php declare(strict_types=1);

namespace Spot\Api\Request;

use Pimple\Container;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\ResponseException;
use Spot\Api\LoggableTrait;

class RequestBus implements RequestBusInterface
{
    use LoggableTrait;

    /** @var  string[] */
    private $executors = [];

    /** @var  Container */
    private $container;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(Container $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function setExecutor(string $name, $executor) : self
    {
        $this->executors[$name] = $executor;
        return $this;
    }

    protected function getExecutor(RequestInterface $request) : ExecutorInterface
    {
        $executor = $this->container[$this->executors[$request->getRequestName()]];
        if (!$executor instanceof ExecutorInterface) {
            throw new \RuntimeException('Executor must implement ExecutorInterface.');
        }
        return $executor;
    }

    protected function supports(RequestInterface $request) : bool
    {
        return array_key_exists($request->getRequestName(), $this->executors)
            && isset($this->container[$this->executors[$request->getRequestName()]]);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage) : ResponseInterface
    {
        if (!$this->supports($requestMessage)) {
            $this->log(LogLevel::WARNING, 'Unsupported request: ' . $requestMessage->getRequestName());
            throw new ResponseException(new NotFoundResponse());
        }

        $requestExecutor = $this->getExecutor($requestMessage);
        $responseMessage = $requestExecutor->executeRequest($requestMessage, $httpRequest);

        return $responseMessage;
    }
}
