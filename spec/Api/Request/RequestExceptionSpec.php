<?php

namespace spec\Spot\Api\Request;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestException;

/** @mixin  RequestException */
class RequestExceptionSpec extends ObjectBehavior
{
    private $httpRequest;

    /**
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     */
    public function let($httpRequest)
    {
        $this->httpRequest = $httpRequest;
        $httpRequest->getHeaderLine('Accept')->willReturn('application/vnd.api+json');
        $this->beConstructedWith('Reasons', null, $httpRequest);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(RequestException::class);
        $this->shouldHaveType(\Exception::class);
    }

    public function it_comesWithARequestObject()
    {
        $request = new Request('destroy.earth', ['not' => 42], $this->httpRequest->getWrappedObject());
        $this->beConstructedWith('Reasons', $request, $this->httpRequest);

        $this->getRequestObject()
            ->shouldReturn($request);
        $this->getMessage()
            ->shouldReturn('Reasons');
    }
}
