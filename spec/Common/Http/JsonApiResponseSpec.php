<?php

namespace spec\Spot\Common\Http;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Common\Http\JsonApiResponse;

/** @mixin  JsonApiResponse */
class JsonApiResponseSpec extends ObjectBehavior
{
    /** @var  \Tobscure\JsonApi\Document */
    private $document;

    /**
     * @param  \Tobscure\JsonApi\Document $document
     */
    public function let($document)
    {
        $this->document = $document;
        $this->beConstructedWith($document);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(JsonApiResponse::class);
    }

    public function it_getsJsonApiContentType()
    {
        $this->getHeaderLine('Content-Type')->shouldReturn('application/vnd.api+json');
    }
}
