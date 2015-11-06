<?php declare(strict_types=1);

namespace Spot\Api\Application\Response;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Application\Response\Generator\GeneratorInterface;
use Spot\Api\Application\Response\Message\ServerErrorResponse;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Common\LoggableTrait;

class ResponseBus implements ResponseBusInterface
{
    use LoggableTrait;

    /** @var  GeneratorInterface[] */
    private $generators = [];

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setGenerator(string $name, GeneratorInterface $generator) : self
    {
        $this->generators[$name] = $generator;
        return $this;
    }

    protected function getGenerator(ResponseInterface $response) : GeneratorInterface
    {
        return $this->generators[$response->getResponseName()];
    }

    /** {@inheritdoc} */
    public function supports(ResponseInterface $response) : bool
    {
        return array_key_exists($response->getResponseName(), $this->generators);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, ResponseInterface $responseMessage) : HttpResponse
    {
        if (!$this->supports($responseMessage)) {
            $this->log('Unsupported request: ' . $responseMessage->getResponseName(), LogLevel::WARNING);
            throw new ResponseException(new ServerErrorResponse(), 500);
        }

        $requestGenerator = $this->getGenerator($responseMessage);
        $httpResponse = $requestGenerator->generateResponse($httpRequest, $responseMessage);

        if (!$httpResponse instanceof HttpResponse) {
            $this->log('Generator for ' . $responseMessage->getResponseName() . ' did not return Response.', LogLevel::ERROR);
            throw new ResponseException(new ServerErrorResponse(), 500);
        }

        return $httpResponse;
    }
}
