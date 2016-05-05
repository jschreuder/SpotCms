<?php declare(strict_types = 1);

namespace Spot\Application\Response;

use Particle\Validator\ValidationResult;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\ResponseException;

class ValidationFailedException extends ResponseException
{
    public function __construct(ValidationResult $result, RequestInterface $request)
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

        parent::__construct('Validation failed', new Response('error.validation', ['errors' => $errors], $request));
    }
}
