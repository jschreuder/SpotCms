<?php

namespace spec\Spot\Auth\Exception;

use PhpSpec\ObjectBehavior;
use Spot\Auth\Exception\LoginFailedException;

/** @mixin LoginFailedException */
class LoginFailedExceptionSpec extends ObjectBehavior
{
    public function it_can_create_bad_email_exception()
    {
        $this->beConstructedThrough('invalidEmailAddress');
        $this->shouldHaveType(LoginFailedException::class);
        $this->getMessage()->shouldReturn(LoginFailedException::ERROR_INVALID_EMAIL_ADDRESS);
    }

    public function it_can_create_bad_credentials_exception()
    {
        $this->beConstructedThrough('invalidCredentials');
        $this->shouldHaveType(LoginFailedException::class);
        $this->getMessage()->shouldReturn(LoginFailedException::ERROR_INVALID_CREDENTIALS);
    }

    public function it_can_create_bad_token_exception()
    {
        $this->beConstructedThrough('invalidToken');
        $this->shouldHaveType(LoginFailedException::class);
        $this->getMessage()->shouldReturn(LoginFailedException::ERROR_INVALID_TOKEN);
    }

    public function it_can_create_general_error_exception()
    {
        $this->beConstructedThrough('systemError');
        $this->shouldHaveType(LoginFailedException::class);
        $this->getMessage()->shouldReturn(LoginFailedException::ERROR_SYSTEM_ERROR);
    }
}
