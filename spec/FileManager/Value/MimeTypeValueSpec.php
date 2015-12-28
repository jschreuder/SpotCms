<?php

namespace spec\Spot\FileManager\Value;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\FileManager\Value\MimeTypeValue;

/** @mixin  MimeTypeValue */
class MimeTypeValueSpec extends ObjectBehavior
{
    private $value;

    public function let()
    {
        $this->value = 'application/vnd.api+json';
        $this->beConstructedThrough('get', [$this->value]);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(MimeTypeValue::class);
    }

    public function it_canGetItsStringValue()
    {
        $this->toString()->shouldReturn($this->value);
    }

    public function it_willNotAcceptInvalidFileNames()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet(" application/json");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("text/html\n");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("an<script>/something");
    }
}
