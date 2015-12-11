<?php declare(strict_types=1);

namespace Spot\Api\Response\Generator;

use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LogLevel;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\Http\JsonApiResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ResponseInterface;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;

class MultiEntityGenerator extends SingleEntityGenerator
{
    public function generateResponse(ResponseInterface $response) : HttpResponse
    {
        if (!$response instanceof Response) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse(['error' => 'Server Error'], 500);
        }
        if (!isset($response['data']) || !is_array($response['data'])) {
            $this->log(LogLevel::ERROR, 'No set of data present in Response.');
            return new JsonApiErrorResponse('Server Error', 500);
        }

        try {
            $collection = (new Collection($response['data'], $this->getSerializer()))
                ->with(isset($response['includes']) ? $response['includes'] : []);
            $document = new Document($collection);
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
