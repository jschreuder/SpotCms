<?php

namespace spec\Spot\Api\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\ArrayRequest;

/** @mixin  \Spot\Api\Request\Message\ArrayRequest */
class ArrayRequestSpec extends ObjectBehavior
{
    private $name = 'array.request';
    private $data = ['answer' => 42];

    public function let()
    {
        $this->beConstructedWith($this->name, $this->data);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(ArrayRequest::class);
    }

    public function it_canGiveItsName()
    {
        $this->getRequestName()
            ->shouldReturn($this->name);
    }

    public function it_canGiveItsData()
    {
        $this->getData()
            ->shouldReturn($this->data);
    }

    public function it_implementsArrayAccess()
    {
        $this->offsetExists('test')->shouldReturn(false);
        $this->shouldThrow(\OutOfBoundsException::class)->duringOffsetGet('test');
        $this->offsetSet('test', 42);
        $this->offsetGet('test')->shouldReturn(42);
        $this->offsetExists('test')->shouldReturn(true);
        $this->offsetUnset('test');
        $this->offsetExists('test')->shouldReturn(false);
    }
}
