<?php

namespace spec\Spot\Api\Response;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\ResponseException;

/** @mixin  ResponseException */
class ResponseExceptionSpec extends ObjectBehavior
{
    public function it_isInitializable()
    {
        $this->shouldHaveType(ResponseException::class);
    }

    public function it_comesWithAResponseObject()
    {
        $response = new ArrayResponse('destroy.earth', ['answer' => 'misfiled']);
        $this->beConstructedWith($response);

        $this->getResponseObject()
            ->shouldReturn($response);
        $this->getMessage()
            ->shouldReturn($response->getResponseName());
    }
}
