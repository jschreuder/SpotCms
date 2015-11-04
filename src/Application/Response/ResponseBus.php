<?php

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
            $this->setGenerator($name, $generator);
        }
        $this->logger = $logger;
    }

    /**
     * @param   string $name
     * @param   callable $generator
     * @return  self
     */
    public function setGenerator($name, callable $generator)
    {
        $this->generators[strval($name)] = $generator;
        return $this;
    }

    /**
     * @param   ResponseInterface $response
     * @return  callable
     */
    protected function getGenerator(ResponseInterface $response)
    {
        return $this->generators[$response->getName()];
    }

    /** {@inheritdoc} */
    public function supports(ResponseInterface $response)
    {
        return array_key_exists($response->getName(), $this->generators);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, ResponseInterface $responseMessage)
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

    /**
     * @param   string $message
     * @param   string $level
     * @return  void
     */
    protected function log($message, $level)
    {
        $this->logger->log($level, $message);
    }
}
