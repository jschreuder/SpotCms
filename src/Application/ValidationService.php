<?php declare(strict_types = 1);

namespace Spot\Application;

use jschreuder\Middle\Exception\ValidationFailedException;
use Laminas\Validator\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class ValidationService
{
    private static function runValidation(array $input, array $validators, array $optionalKeys): void
    {
        $errors = [];
        foreach ($validators as $key => $validator) {
            // Always error when invalid validators are given
            if (!$validator instanceof ValidatorInterface) {
                throw new RuntimeException('Invalid validator', 500);
            }

            $value = static::dotnotatedFromArray($key, $input);
            // Skip optional fields only when not in input or null, all other inputs are considered
            if (in_array($key, $optionalKeys) && is_null($value)) {
                continue;
            }
            // Register errors when validation fails
            if (!$validator->isValid($value)) {
                $errors[$key] = $validator->getMessages();
            }
        }
        // Validation failed when one or more errors were given
        if ($errors) {
            throw new ValidationFailedException($errors);
        }
    }

    private static function dotnotatedFromArray(string $key, array $array): mixed
    {
        $keys = explode('.', $key);
        while ($sub = array_shift($keys)) {
            if (!isset($array[$sub])) {
                return null;
            }
            $array = $array[$sub];
        }
        return $array;
    }

    public static function validate(
        ServerRequestInterface $request, 
        array $validators,
        array $optionalKeys = []
    ): void
    {
        self::runValidation((array) $request->getParsedBody(), $validators, $optionalKeys);
    }

    public static function validateQuery(
        ServerRequestInterface $request, 
        array $validators,
        array $optionalKeys = []
    ): void
    {
        self::runValidation((array) $request->getQueryParams(), $validators, $optionalKeys);
    }

    public static function requireUploads(ServerRequestInterface $request): void
    {
        $files = $request->getUploadedFiles();
        if (!is_array($files) || count($files) === 0) {
            throw new ValidationFailedException(['_FILES' => 'No uploaded files in request']);
        }
    }
}
