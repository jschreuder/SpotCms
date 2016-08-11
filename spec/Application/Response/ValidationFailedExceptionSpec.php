<?php

namespace spec\Spot\Application\Response;

use Particle\Validator\ValidationResult;
use PhpSpec\ObjectBehavior;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Response\ValidationFailedException;

/** @mixin ValidationFailedException */
class ValidationFailedExceptionSpec extends ObjectBehavior
{
    /** @var  ValidationResult */
    private $result;

    /** @var  RequestInterface */
    private $request;

    /** @var  array */
    private $messages;

    public function let(ValidationResult $result, RequestInterface $request)
    {
        $this->result = $result;
        $this->request = $request;
        $this->beConstructedWith($result, $request);

        $this->request->getAcceptContentType()->willReturn('*/*');

        $this->messages = [
            'field' => [
                'Error::EXAMPLE' => 'This is an example message',
            ],
        ];
        $this->result->getMessages()
            ->willReturn($this->messages);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ValidationFailedException::class);
    }

    public function it_should_have_a_request_object()
    {
        $request = $this->getResponseObject();
        $request->shouldHaveType(ResponseInterface::class);
        $request->offsetGet('errors')->shouldReturn([
            [
                'title' => reset($this->messages['field']),
                'code' => key($this->messages['field']),
                'source' => ['parameter' => key($this->messages)],
            ],
        ]);
    }
}
