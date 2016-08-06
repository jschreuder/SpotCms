<?php

namespace spec\Spot\Application\Request;

use Particle\Validator\ValidationResult;
use PhpSpec\ObjectBehavior;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Request\ValidationFailedException;

/** @mixin  ValidationFailedException */
class ValidationFailedExceptionSpec extends ObjectBehavior
{
    /** @var  \Particle\Validator\ValidationResult */
    private $result;

    /** @var  \Psr\Http\Message\RequestInterface */
    private $httpRequest;

    /** @var  array */
    private $messages;

    public function let(ValidationResult $result, \Psr\Http\Message\RequestInterface $httpRequest)
    {
        $this->result = $result;
        $this->httpRequest = $httpRequest;
        $this->beConstructedWith($result, $httpRequest);

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
        $request = $this->getRequestObject();
        $request->shouldHaveType(RequestInterface::class);
        $request->offsetGet('errors')->shouldReturn([
            [
                'id' => key($this->messages) . '::' . key($this->messages['field']),
                'title' => reset($this->messages['field']),
                'code' => key($this->messages['field']),
                'source' => ['parameter' => key($this->messages)],
            ],
        ]);
    }
}
