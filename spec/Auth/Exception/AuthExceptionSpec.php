<?php

namespace spec\Spot\Auth\Exception;

use PhpSpec\ObjectBehavior;
use Spot\Auth\Exception\AuthException;

/** @mixin AuthException */
class AuthExceptionSpec extends ObjectBehavior
{
    /** @var  string */
    private $msg = 'This is an error';

    /** @var  int */
    private $code = 500;

    public function let()
    {
        $this->beConstructedWith($this->msg, $this->code);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AuthException::class);
    }

    public function it_errors_on_invalid_code()
    {
        $this->beConstructedWith($this->msg, 300);
        $this->shouldThrow(\RuntimeException::class)->duringInstantiation();
    }
}
