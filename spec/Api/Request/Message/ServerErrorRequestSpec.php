<?php

namespace spec\Spot\Api\Request\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\ServerErrorRequest;

/** @mixin  \Spot\Api\Request\Message\ServerErrorRequest */
class ServerErrorRequestSpec extends ObjectBehavior
{
    private $name = 'error.serverError';

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
        $this->shouldHaveType(ServerErrorRequest::class);
    }

    public function it_canGiveItsName()
    {
        $this->getRequestName()
            ->shouldReturn($this->name);
    }
}
