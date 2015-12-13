<?php declare(strict_types=1);

namespace Spot\Api\Http;

use Tobscure\JsonApi\Document;

class JsonApiErrorResponse extends JsonApiResponse
{
    /**
     * JsonResponse overloaded Constructor to assign JSON-API Content-Type
     * and create JSON-API formatted response
     */
    public function __construct(string $message, int $status = 200, array $meta = null)
    {
        $error = [
            'title' => $message,
            'status' => strval($status),
        ];
        if (!is_null($meta)) {
            $error['meta'] = $meta;
        }

        $document = new Document();
        $document->setErrors([$error]);
        parent::__construct($document, $status);
    }
}
