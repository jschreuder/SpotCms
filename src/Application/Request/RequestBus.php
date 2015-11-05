<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Cms\Application\Request\Executor\ExecutorInterface;
use Spot\Cms\Application\Request\Message\NotFoundRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Response\Message\ResponseInterface;
use Spot\Cms\Application\Response\Message\ServerErrorResponse;
use Spot\Cms\Application\Response\ResponseException;
use Spot\Cms\Common\LoggableTrait;

class RequestBus implements RequestBusInterface
{
    use LoggableTrait;

    /** @var  ExecutorInterface[] */
    private $executors = [];

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setExecutor(string $name, ExecutorInterface $executor) : self
    {
        $this->executors[$name] = $executor;
        return $this;
    }

    protected function getExecutor(RequestInterface $request) : ExecutorInterface
    {
        return $this->executors[$request->getRequestName()];
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
