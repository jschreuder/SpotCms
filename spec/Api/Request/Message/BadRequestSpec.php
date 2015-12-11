<?php

namespace spec\Spot\Api\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\BadRequest;

/** @mixin  \Spot\Api\Request\Message\BadRequest */
class BadRequestSpec extends ObjectBehavior
{
    private $name = 'error.badRequest';

    /** @var  \Psr\Http\Message\RequestInterface */
    private $httpRequest;

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     */
    public function let($httpRequest)
    {
        $this->httpRequest = $httpRequest;
        $httpRequest->getHeaderLine('Accept')->willReturn('application/vnd.api+json');
        $this->beConstructedWith([], $httpRequest);
    }

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
