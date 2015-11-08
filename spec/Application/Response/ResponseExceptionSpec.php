<?php

namespace spec\Spot\Api\Application\Response;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Application\Response\Message\ArrayResponse;
use Spot\Api\Application\Response\ResponseException;

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
