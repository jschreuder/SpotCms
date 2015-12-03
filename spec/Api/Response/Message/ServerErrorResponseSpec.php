<?php

namespace spec\Spot\Api\Response\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Response\Message\ServerErrorResponse;

/** @mixin  \Spot\Api\Response\Message\ServerErrorResponse */
class ServerErrorResponseSpec extends ObjectBehavior
{
    private $name = 'error.serverError';

    public function it_isInitializable()
    {
        $this->shouldHaveType(ServerErrorResponse::class);
    }

    public function it_canGiveItsName()
    {
        $this->getResponseName()
            ->shouldReturn($this->name);
    }
}