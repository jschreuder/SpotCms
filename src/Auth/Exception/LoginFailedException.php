<?php declare(strict_types = 1);

namespace Spot\Auth\Exception;

class LoginFailedException extends AuthException
{
    const ERROR_INVALID_EMAIL_ADDRESS = 'invalid.email_address';
    const ERROR_INVALID_CREDENTIALS = 'invalid.credentials';
    const ERROR_INVALID_TOKEN = 'invalid.token';
    const ERROR_SYSTEM_ERROR = 'invalid.system_error';

    public static function invalidEmailAddress(\Throwable $previous = null) : LoginFailedException
    {
        return new self(self::ERROR_INVALID_EMAIL_ADDRESS, 400, $previous);
    }

    public static function invalidCredentials(\Throwable $previous = null) : LoginFailedException
    {
        return new self(self::ERROR_INVALID_CREDENTIALS, 400, $previous);
    }

    public static function invalidToken(\Throwable $previous = null) : LoginFailedException
    {
        return new self(self::ERROR_INVALID_TOKEN, 401, $previous);
    }

    public static function systemError(\Throwable $previous = null) : LoginFailedException
    {
        return new self(self::ERROR_SYSTEM_ERROR, 500, $previous);
    }
}
