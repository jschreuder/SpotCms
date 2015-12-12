<?php declare(strict_types=1);

namespace Spot\Api\Response;

use Pimple\Container;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\LoggableTrait;
use Zend\Diactoros\Response;

class ResponseBus implements ResponseBusInterface
{
    use LoggableTrait;

    /** @var  string[][] */
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

    public function setGenerator(string $name, string $type, $generator) : self
    {
        $this->generators[$name][$type] = $generator;
        return $this;
    }

    private function hasGenerator(string $name, string $contentType) : bool
    {
        return isset($this->generators[$name][$contentType]);
    }

    private function getGenerator(string $name, string $contentType) : GeneratorInterface
    {
        return $this->container[$this->generators[$name][$contentType]];
    }

    private function getFirstGenerator(string $name) : GeneratorInterface
    {
        return $this->container[reset($this->generators[$name])];
    }

    protected function getGeneratorForResponse(ResponseInterface $response) : GeneratorInterface
    {
        $name = $response->getResponseName();
        if (empty($this->generators[$name])) {
            throw new \OutOfBoundsException('No generator registered for this response: ' . $name);
        }

        // Attempt to match content-type
        foreach ($this->getRequestedContentTypes($response) as $contentType) {
            if ($this->hasGenerator($name, $contentType)) {
                return $this->getGenerator($name, $contentType);
            } elseif ($contentType === '*/*') {
                return $this->getFirstGenerator($name);
            }
        }

        throw new \OutOfBoundsException(
            'No generator registered for this content type: ' . ($contentType ?? '(none)')
        );
    }

    private function getRequestedContentTypes(ResponseInterface $response)
    {
        preg_match_all(
            '#(?:^|(?:, ?))(?P<type>[^/,;]+/[^/,;]+)[^,]*?(?:;q=(?P<weight>[01]\.[0-9]+))?#uiD',
            $response->getContentType(),
            $matches
        );
        $types = new \SplPriorityQueue();
        foreach ($matches['type'] as $idx => $type) {
            $types->insert($type, $matches['weight'][$idx] ?: 1.0);
        }
        return $types;
    }

    /** {@inheritdoc} */
    public function supports(ResponseInterface $response) : bool
    {
        return array_key_exists($response->getResponseName(), $this->generators);
    }

    /** {@inheritdoc} */
    public function execute(ResponseInterface $response) : HttpResponse
    {
        if (!$this->supports($response)) {
            $this->log(LogLevel::ERROR, 'Unsupported response: ' . $response->getResponseName());
            return new JsonApiErrorResponse('Server error', 500);
        }

        try {
            $requestGenerator = $this->getGeneratorForResponse($response);
            $httpResponse = $requestGenerator->generateResponse($response);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, 'Error during Response generation: ' . $e->getMessage());
            return new JsonApiErrorResponse('Server error', 500);
        }

        return $httpResponse;
    }
}
