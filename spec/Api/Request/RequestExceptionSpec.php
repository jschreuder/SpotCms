<?php

namespace spec\Spot\Api\Request;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestException;

/** @mixin  RequestException */
class RequestExceptionSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('Reasons');
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(RequestException::class);
        $this->shouldHaveType(\Exception::class);
    }

    public function it_comesWithARequestObject()
    {
        $request = new Request('destroy.earth', ['not' => 42]);
        $this->beConstructedWith('Reasons', $request);

        $this->getRequestObject()
            ->shouldReturn($request);
        $this->getMessage()
            ->shouldReturn('Reasons');
    }
}
