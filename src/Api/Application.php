<?php declare(strict_types=1);

namespace Spot\Api;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Api\Request\RequestBusInterface;
use Spot\Api\Request\RequestException;
use Spot\Api\Response\ResponseBusInterface;
use Spot\Api\Response\ResponseException;
use Spot\Common\LoggableTrait;

class Application implements ApplicationInterface
{
    use LoggableTrait;

    /** @var  HttpRequestParserInterface */
    private $requestParser;

    /** @var  RequestBusInterface */
    private $requestBus;

    /** @var  ResponseBusInterface */
    private $responseBus;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(
        HttpRequestParserInterface $requestParser,
        RequestBusInterface $requestBus,
        ResponseBusInterface $responseBus,
        LoggerInterface $logger
    ) {
        $this->requestParser = $requestParser;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;
        $this->logger = $logger;
    }

    /** {@inheritdoc} */
    public function execute(ServerHttpRequest $httpRequest) : HttpResponse
    {
        $this->log(LogLevel::INFO, 'Starting execution.');
        try {
            $requestMessage = $this->requestParser->parseHttpRequest($httpRequest, []);
            $this->log(LogLevel::INFO, 'Successfully parsed HTTP request into Request message.');
        } catch (RequestException $requestException) {
            $this->log(LogLevel::ERROR, 'Request parsing ended in exception: ' . $requestException->getMessage());
            $requestMessage = $requestException->getRequestObject();
        }

        try {
            $responseMessage = $this->requestBus->execute($httpRequest, $requestMessage);
        } catch (ResponseException $responseException) {
            $this->log(LogLevel::ERROR, 'Request execution ended in exception: ' . $responseException->getMessage());
            $responseMessage = $responseException->getResponseObject();
        }

        $httpResponse = $this->responseBus->execute($httpRequest, $responseMessage);
        $this->log(LogLevel::INFO, 'Successfully generated HTTP response.');

        return $httpResponse;
    }
}
