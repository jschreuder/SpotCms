<?php declare(strict_types=1);

namespace Spot\Api\Response\Generator;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\Http\JsonApiResponse;
use Spot\Api\LoggableTrait;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

class SingleEntityGenerator implements GeneratorInterface
{
    use LoggableTrait;

    /** @var  SerializerInterface */
    private $serializer;

    /** @var  callable */
    private $metaDataGenerator;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(
        SerializerInterface $serializer,
        callable $metaDataGenerator = null,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->metaDataGenerator = $metaDataGenerator;
        $this->logger = $logger;
    }

    protected function metaDataGenerator(ResponseInterface $response) : array
    {
        return $this->metaDataGenerator ? call_user_func($this->metaDataGenerator, $response) : [];
    }

    protected function getSerializer() : SerializerInterface
    {
        return $this->serializer;
    }

    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse('Server Error', 500);
        }
        if (!isset($response['data'])) {
            $this->log(LogLevel::ERROR, 'No data present in Response.');
            return new JsonApiErrorResponse('Server Error', 500);
        }

        try {
            $resource = (new Resource($response['data'], $this->getSerializer()))
                ->with(isset($response['includes']) ? $response['includes'] : []);
            $document = new Document($resource);
            foreach ($this->metaDataGenerator($response) as $key => $value) {
                $document->addMeta($key, $value);
            }
            return new JsonApiResponse($document);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, 'Error occurred during Response generation: ' . $e->getMessage());
            return new JsonApiErrorResponse('Server Error', 500);
        }
    }
}
