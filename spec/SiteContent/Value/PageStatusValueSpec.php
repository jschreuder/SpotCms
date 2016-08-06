<?php

namespace spec\Spot\SiteContent\Value;

use PhpSpec\ObjectBehavior;
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

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageStatusValue::class);
    }

    public function it_can_get_its_string_value()
    {
        $this->toString()->shouldReturn($this->value);
    }

    public function it_throws_exception_on_invalid_state_value()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet('nope');
    }
}
