<?php declare(strict_types=1);

namespace Spot\Common\Request;

use Particle\Validator\ValidationResult;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\RequestException;

class ValidationFailedException extends RequestException
{
    /** @var  string[] */
    private $errors;

    public function __construct(ValidationResult $result)
    {
        $this->errors = [];
        foreach ($result->getMessages() as $field => $messages) {
            foreach ($messages as $errorKey => $errorMessage) {
                $this->errors[] = sprintf('[%s] :: %s (%s)', $field, $errorMessage, $errorKey);
            }
        }

        parent::__construct(implode("\n", $this->errors), new BadRequest());
    }

    public function getErrors() : array
    {
        return $this->errors;
    }
}
