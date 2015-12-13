<?php

namespace spec\Spot\Common\Request;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Common\Request\ValidationFailedException;

/** @mixin  ValidationFailedException */
class ValidationFailedExceptionSpec extends ObjectBehavior
{
    /** @var  \Particle\Validator\ValidationResult */
    private $result;

    /** @var  \Psr\Http\Message\RequestInterface */
    private $httpRequest;

    /** @var  array */
    private $messages;

    /**
     * @param  \Particle\Validator\ValidationResult $result
     * @param  \Psr\Http\Message\RequestInterface $httpRequest
     */
    public function let($result, $httpRequest)
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

    public function it_isInitializable()
    {
        $this->shouldHaveType(ValidationFailedException::class);
    }

    public function it_shouldHaveARequestObject()
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
