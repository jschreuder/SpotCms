<?php declare(strict_types=1);

namespace Spot\Api\Response;

use Pimple\Container;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Common\Http\JsonApiErrorResponse;
use Spot\Common\LoggableTrait;
use Zend\Diactoros\Response;

class ResponseBus implements ResponseBusInterface
{
    use LoggableTrait;

    /** @var  string[] */
    private $generators = [];

    /** @var  Container */
    private $container;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(Container $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function setGenerator(string $name, $generator) : self
    {
        $this->generators[$name] = $generator;
        return $this;
    }

    protected function getGenerator(ResponseInterface $response) : GeneratorInterface
    {
        $generator = $this->container[$this->generators[$response->getResponseName()]];
        if (!$generator instanceof GeneratorInterface) {
            throw new \RuntimeException('Generator must implement GeneratorInterface.');
        }
        return $generator;
    }

    /** {@inheritdoc} */
    public function supports(ResponseInterface $response) : bool
    {
        return array_key_exists($response->getResponseName(), $this->generators)
            && isset($this->container[$this->generators[$response->getResponseName()]]);
    }

    /** {@inheritdoc} */
    public function execute(HttpRequest $httpRequest, ResponseInterface $responseMessage) : HttpResponse
    {
        if (!$this->supports($responseMessage)) {
            $this->log(LogLevel::WARNING, 'Unsupported request: ' . $responseMessage->getResponseName());
            return new JsonApiErrorResponse('Server error', 500);
        }

        $requestGenerator = $this->getGenerator($responseMessage);
        $httpResponse = $requestGenerator->generateResponse($responseMessage, $httpRequest);

        return $httpResponse;
    }
}
