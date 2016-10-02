<?php declare(strict_types = 1);

namespace Spot\Application\Http;

use Spot\Application\View\JsonApiViewInterface;
use Tobscure\JsonApi\Document;
use Zend\Diactoros\Response\JsonResponse;

class JsonApiErrorResponse extends JsonResponse
{
    public function __construct(array $errors, int $status, array $headers = [])
    {
        $data = new Document();
        $data->setErrors($this->formatErrors($errors));

        $headers['Content-Type'] = JsonApiViewInterface::CONTENT_TYPE_JSON_API;
        parent::__construct($data, $status, $headers);
    }

    private function formatErrors(array $errors) : array
    {
        $formatted = [];
        foreach ($errors as $code => $message) {
            $error = ['title' => $message];
            if (!is_int($code)) {
                $error['code'] = $code;
            }
            $formatted[] = $error;
        }
        return $formatted;
    }
}
