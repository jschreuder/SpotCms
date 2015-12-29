<?php

namespace spec\Spot\SiteContent\Value;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageStatusValue */
class PageStatusValueSpec extends ObjectBehavior
{
    private $value;

    public function let()
    {
        $this->value = 'published';
        $this->beConstructedThrough('get', [$this->value]);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(PageStatusValue::class);
    }

    public function it_canGetItsStringValue()
    {
        $this->toString()->shouldReturn($this->value);
    }

    public function it_throwsExceptionOnInvalidStateValue()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet('nope');
    }
}
