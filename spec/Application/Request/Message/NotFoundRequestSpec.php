<?php

namespace spec\Spot\Api\Application\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Request\Message\NotFoundRequest;

/** @mixin  \Spot\Api\Application\Request\Message\NotFoundRequest */
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
