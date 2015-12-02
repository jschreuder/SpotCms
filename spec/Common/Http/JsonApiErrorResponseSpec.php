<?php

namespace spec\Spot\Common\Http;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Common\Http\JsonApiErrorResponse;

/** @mixin  JsonApiErrorResponse */
class JsonApiErrorResponseSpec extends ObjectBehavior
{
    /** @var  string */
    private $message;

    /** @var  int */
    private $code;

    public function let()
    {
        $this->message = 'Test message';
        $this->code = 418;
        $this->beConstructedWith($this->message, $this->code);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(JsonApiErrorResponse::class);
    }

    public function it_getsJsonApiContentType()
    {
        $this->getHeaderLine('Content-Type')->shouldReturn('application/vnd.api+json');
    }

    public function it_getJsonApiBody()
    {
        $body = $this->getBody();
        $body->rewind();
        $body->getContents()->shouldReturn('{"errors":[{"title":"Test message","status":"418"}]}');
    }
}
