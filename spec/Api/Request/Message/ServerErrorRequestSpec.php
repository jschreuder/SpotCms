<?php

namespace spec\Spot\Api\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\ServerErrorRequest;

/** @mixin  \Spot\Api\Request\Message\ServerErrorRequest */
class ServerErrorRequestSpec extends ObjectBehavior
{
    private $name = 'error.serverError';

    public function it_isInitializable()
    {
        $this->shouldHaveType(ServerErrorRequest::class);
    }

    public function it_canGiveItsName()
    {
        $this->getRequestName()
            ->shouldReturn($this->name);
    }
}
