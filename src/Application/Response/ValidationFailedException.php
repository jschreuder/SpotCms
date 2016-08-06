<?php declare(strict_types = 1);

namespace Spot\Application\Response;

use Particle\Validator\ValidationResult;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\ResponseException;
use Spot\Application\Validator\ParseMessagesToJsonApiErrorsTrait;

class ValidationFailedException extends ResponseException
{
    use ParseMessagesToJsonApiErrorsTrait;

    public function __construct(ValidationResult $result, RequestInterface $request)
    {
        parent::__construct(
            'Validation failed',
            new Response('error.validation', ['errors' => $this->parseMessages($result)], $request)
        );
    }
}
