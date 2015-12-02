<?php declare(strict_types=1);

namespace Spot\Api\Response\Generator;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LogLevel;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\Http\JsonApiResponse;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;

class MultiEntityGenerator extends SingleEntityGenerator
{
    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse(['error' => 'Server Error'], 500);
        }

        $document = new Document(new Collection($response['data'], $this->getSerializer()));
        foreach ($this->metaDataGenerator($response) as $key => $value) {
            $document->addMeta($key, $value);
        }
        return new JsonApiResponse($document);
    }
}
