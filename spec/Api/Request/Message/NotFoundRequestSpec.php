<?php

namespace spec\Spot\Api\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\NotFoundRequest;

/** @mixin  \Spot\Api\Request\Message\NotFoundRequest */
class NotFoundRequestSpec extends ObjectBehavior
{
    private $name = 'error.notFound';

    public function it_isInitializable()
    {
        $this->shouldHaveType(NotFoundRequest::class);
    }

    public function it_canGiveItsName()
    {
        $this->getRequestName()
            ->shouldReturn($this->name);
    }
}
