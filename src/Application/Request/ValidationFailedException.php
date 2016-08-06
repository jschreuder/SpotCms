<?php declare(strict_types = 1);

namespace Spot\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Particle\Validator\ValidationResult;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\RequestException;
use Spot\Application\Validator\ParseMessagesToJsonApiErrorsTrait;

class ValidationFailedException extends RequestException
{
    use ParseMessagesToJsonApiErrorsTrait;

    public function __construct(ValidationResult $result, HttpRequestInterface $httpRequest)
    {
        parent::__construct(
            'Validation failed',
            new BadRequest(['errors' => $this->parseMessages($result)], $httpRequest)
        );
    }
}
