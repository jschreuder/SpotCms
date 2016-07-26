<?php

namespace spec\Spot\Auth\Value;

use PhpSpec\ObjectBehavior;
use Spot\Auth\Value\EmailAddress;

/** @mixin  EmailAddress */
class EmailAddressSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedThrough('get', ['lando@calrissian.cloud']);
        $this->shouldHaveType(EmailAddress::class);
    }

    public function it_errors_on_invalid_email_address()
    {
        $this->beConstructedThrough('get', ['lando.calrissian.cloud']);
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
