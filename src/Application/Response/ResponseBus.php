<?php declare(strict_types=1);

namespace Spot\Cms\Application\Response;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Cms\Application\Response\Message\ServerError;
use Spot\Cms\Application\Response\Message\ResponseInterface;

class ResponseBus implements ResponseBusInterface
{
    /** @var  callable[] */
    private $generators = [];

    /** @var  LoggerInterface */
    private $logger;

    /**
     * @param  callable[] $generators
     * @param  LoggerInterface $logger
     */
    public function __construct(array $generators = [], LoggerInterface $logger)
    {
        foreach ($generators as $name => $generator) {
            $this->setGenerator(strval($name), $generator);
        }
        $this->logger = $logger;
    }

    public function setGenerator(string $name, callable $generator) : self
    {
        $this->generators[$name] = $generator;
        return $this;
    }

    protected function getGenerator(ResponseInterface $response) : callable
    {
        return $this->generators[$response->getName()];
    }

    /** {@inheritdoc} */
    public function supports(ResponseInterface $response) : bool
    {
        return array_key_exists($response->getName(), $this->generators);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, ResponseInterface $responseMessage) : HttpResponse
    {
        if (!$this->supports($responseMessage)) {
            $this->log('Unsupported request: ' . $responseMessage->getName(), LogLevel::WARNING);
            throw new ResponseException(new ServerError(), 500);
        }

        $requestGenerator = $this->getGenerator($responseMessage);
        $httpResponse = $requestGenerator($httpRequest, $responseMessage);

        if (!$httpResponse instanceof HttpResponse) {
            $this->log('Generator for ' . $responseMessage->getName() . ' did not return Response.', LogLevel::ERROR);
            throw new ResponseException(new ServerError(), 500);
        }

        return $httpResponse;
    }

    protected function log(string $message, string $level)
    {
        $this->logger->log($level, '[ResponseBus] ' . $message);
    }
}
