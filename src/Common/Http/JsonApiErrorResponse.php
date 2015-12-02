<?php declare(strict_types=1);

namespace Spot\Common\Http;

use Tobscure\JsonApi\Document;

class JsonApiErrorResponse extends JsonApiResponse
{
    /**
     * JsonResponse overloaded Constructor to assign JSON-API Content-Type
     * and create JSON-API formatted response
     */
    public function __construct(string $message, int $status = 200)
    {
        $document = new Document();
        $document->setErrors([[
            'title' => $message,
            'status' => strval($status),
        ]]);
        parent::__construct($document, $status);
    }
}
