<?php

namespace spec\Spot\Api\Application\Request;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Request\Message\ArrayRequest;
use Spot\Api\Application\Request\RequestException;

/** @mixin  RequestException */
class RequestExceptionSpec extends ObjectBehavior
{
    public function it_isInitializable()
    {
        $this->shouldHaveType(RequestException::class);
        $this->shouldHaveType(\Exception::class);
    }

    public function it_comesWithARequestObject()
    {
        $request = new ArrayRequest('destroy.earth', ['not' => 42]);
        $this->beConstructedWith($request);

        $this->getRequestObject()
            ->shouldReturn($request);
        $this->getMessage()
            ->shouldReturn($request->getRequestName());
    }
}
