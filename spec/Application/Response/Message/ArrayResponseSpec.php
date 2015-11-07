<?php

namespace spec\Spot\Api\Application\Response\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Response\Message\ArrayResponse;

/** @mixin  \Spot\Api\Application\Response\Message\ArrayResponse */
class ArrayResponseSpec extends ObjectBehavior
{
    private $name = 'array.response';
    private $data = ['answer' => 42];

    public function let()
    {
        $this->beConstructedWith($this->name, $this->data);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(ArrayResponse::class);
    }

    public function it_canGiveItsName()
    {
        $this->getResponseName()
            ->shouldReturn($this->name);
    }

    public function it_canGiveItsData()
    {
        $this->getData()
            ->shouldReturn($this->data);
    }
}
