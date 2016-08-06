<?php declare(strict_types = 1);

namespace Spot\Application\Validator;

use Particle\Validator\ValidationResult;

trait ParseMessagesToJsonApiErrorsTrait
{
    private function parseMessages(ValidationResult $result)
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
        return $errors;
    }
}
