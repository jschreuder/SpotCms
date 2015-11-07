<?php

namespace spec\Spot\Api\Application\Response\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Response\Message\NotFoundResponse;

/** @mixin  \Spot\Api\Application\Response\Message\NotFoundResponse */
class NotFoundResponseSpec extends ObjectBehavior
{
    private $name = 'error.notFound';

    public function it_isInitializable()
    {
        $this->shouldHaveType(NotFoundResponse::class);
    }

    public function it_canGiveItsName()
    {
        $this->getResponseName()
            ->shouldReturn($this->name);
    }
}
