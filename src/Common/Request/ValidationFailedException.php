<?php declare(strict_types = 1);

namespace Spot\Common\Request;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Particle\Validator\ValidationResult;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\RequestException;

class ValidationFailedException extends RequestException
{
    public function __construct(ValidationResult $result, HttpRequestInterface $httpRequest)
    {
        $errors = [];
        foreach ($result->getMessages() as $field => $messages) {
            foreach ($messages as $errorKey => $errorMessage) {
                $errors[] = [
                    'id' => $field . '::' . $errorKey,
                    'title' => $errorMessage,
                    'code' => $errorKey,
                    'source' => ['parameter' => $field],
                ];
            }
        }

        parent::__construct('Validation failed', new BadRequest(['errors' => $errors], $httpRequest));
    }
}
