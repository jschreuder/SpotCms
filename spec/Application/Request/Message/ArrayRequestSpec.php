<?php

namespace spec\Spot\Api\Application\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Request\Message\ArrayRequest;

/** @mixin  \Spot\Api\Application\Request\Message\ArrayRequest */
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
}
