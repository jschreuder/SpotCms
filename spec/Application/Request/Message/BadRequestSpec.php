<?php

namespace spec\Spot\Api\Application\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Request\Message\BadRequest;

/** @mixin  \Spot\Api\Application\Request\Message\BadRequest */
class BadRequestSpec extends ObjectBehavior
{
    private $name = 'error.badRequest';

    public function it_isInitializable()
    {
        $this->shouldHaveType(BadRequest::class);
    }

    public function it_canGiveItsName()
    {
        $this->getRequestName()
            ->shouldReturn($this->name);
    }
}
