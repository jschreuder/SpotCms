<?php declare(strict_types=1);

namespace Spot\Api\Application\Request;

use Pimple\Container;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Application\Request\Executor\ExecutorInterface;
use Spot\Api\Application\Request\Message\NotFoundRequest;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Application\Response\Message\ServerErrorResponse;
use Spot\Api\Application\Response\ResponseException;
use Spot\Api\Common\LoggableTrait;

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

    /** {@inheritdoc} */
    public function supports(RequestInterface $request) : bool
    {
        return array_key_exists($request->getRequestName(), $this->executors);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage) : ResponseInterface
    {
        if (!$this->supports($requestMessage)) {
            $this->log('Unsupported request: ' . $requestMessage->getRequestName(), LogLevel::WARNING);
            throw new ResponseException(new NotFoundRequest(), 404);
        }

        $requestExecutor = $this->getExecutor($requestMessage);
        $responseMessage = $requestExecutor->executeRequest($requestMessage, $httpRequest);

        if (!$responseMessage instanceof ResponseInterface) {
            $this->log('Executor for ' . $requestMessage->getRequestName() . ' did not return Response.', LogLevel::ERROR);
            throw new ResponseException(new ServerErrorResponse(), 500);
        }

        return $responseMessage;
    }
}
