<?php

namespace spec\Spot\Application\Response;

use Particle\Validator\ValidationResult;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Response\ValidationFailedException;

/** @mixin ValidationFailedException */
class ValidationFailedExceptionSpec extends ObjectBehavior
{
    /** @var  ValidationResult */
    private $result;

    /** @var  RequestInterface */
    private $request;

    public function let(ValidationResult $result, RequestInterface $request)
    {
        $this->result = $result;
        $this->request = $request;
        $this->beConstructedWith($result, $request);
    }

    public function it_is_initializable()
    {
        $this->result->getMessages()->willReturn([]);
        $this->request->getAcceptContentType()->willReturn('*/*');
        $this->shouldHaveType(ValidationFailedException::class);
    }
}
