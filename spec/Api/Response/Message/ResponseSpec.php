<?php

namespace spec\Spot\Api\Response\Message;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use spec\Spot\Api\Message\AttributesArrayAccessSpecTrait;
use Spot\Api\Response\Message\Response;

require_once __DIR__ . '/../../Message/AttributesArrayAccessSpecTrait.php';

/** @mixin  \Spot\Api\Response\Message\Response */
class ResponseSpec extends ObjectBehavior
{
    use AttributesArrayAccessSpecTrait;

    private $name = 'array.response';
    private $data = ['answer' => 42];
    private $request;

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function let($request)
    {
        $this->request = $request;
        $request->getAcceptContentType()->willReturn('application/vnd.api+json');
        $this->beConstructedWith($this->name, $this->data, $request);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(Response::class);
    }

    public function it_canGiveItsName()
    {
        $this->getResponseName()
            ->shouldReturn($this->name);
    }

    public function it_canGiveItsData()
    {
        $this->getAttributes()
            ->shouldReturn($this->data);
    }

    public function it_implementsArrayAccess()
    {
        $this->offsetExists('test')->shouldReturn(false);
        $this->shouldThrow(\OutOfBoundsException::class)->duringOffsetGet('test');
        $this->offsetSet('test', 42);
        $this->offsetGet('test')->shouldReturn(42);
        $this->offsetExists('test')->shouldReturn(true);
        $this->offsetUnset('test');
        $this->offsetExists('test')->shouldReturn(false);
    }
}
