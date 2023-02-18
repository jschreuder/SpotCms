<?php declare(strict_types = 1);

namespace Spot\Application\Http;

use Laminas\Diactoros\Response\JsonResponse;

class JsonApiErrorResponse extends JsonResponse
{
    public function __construct(array $errors, int $status, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
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
