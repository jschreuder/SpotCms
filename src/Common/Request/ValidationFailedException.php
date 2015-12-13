<?php declare(strict_types=1);

namespace Spot\Common\Request;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Particle\Validator\ValidationResult;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\RequestException;

class ValidationFailedException extends RequestException
{
    /** @var  string[] */
    private $errors;

    public function __construct(ValidationResult $result, HttpRequestInterface $httpRequest)
    {
        $this->errors = [];
        foreach ($result->getMessages() as $field => $messages) {
            foreach ($messages as $errorKey => $errorMessage) {
                $this->errors[$field][] = $errorKey;
            }
        }

        parent::__construct('Validation failed', new BadRequest(['errors' => $this->errors], $httpRequest));
    }

    public function getErrors() : array
    {
        return $this->errors;
    }
}
