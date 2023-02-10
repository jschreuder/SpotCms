<?php declare(strict_types = 1);

namespace Spot\Application\Http;

use Laminas\Diactoros\Response\JsonResponse;
use Spot\Application\View\JsonApiViewInterface;

class JsonApiErrorResponse extends JsonResponse
{
    public function __construct(array $errors, int $status, array $headers = [])
    {
        $headers['Content-Type'] = JsonApiViewInterface::CONTENT_TYPE_JSON_API;
        parent::__construct(['errors' => $this->formatErrors($errors)], $status, $headers);
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
