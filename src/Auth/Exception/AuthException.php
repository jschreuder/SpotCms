<?php declare(strict_types = 1);

namespace Spot\Auth\Exception;

use Exception;

class AuthException extends \Exception
{
    public function __construct($message, $code, Exception $previous = null)
    {
        if ($code < 400 || $code >= 600) {
            throw new \RuntimeException(
                'AuthException code must be valid HTTP status code [' . $message . ']',
                500,
                $previous
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
