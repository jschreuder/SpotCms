<?php

namespace spec\Spot\Api\Response\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Response\Message\NotFoundResponse;

/** @mixin  \Spot\Api\Response\Message\NotFoundResponse */
class NotFoundResponseSpec extends ObjectBehavior
{
    private $name = 'error.notFound';
    private $request;

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function let($request)
    {
        $this->request = $request;
        $request->getAcceptContentType()->willReturn('application/vnd.api+json');
        $this->beConstructedWith([], $request);
    }

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
