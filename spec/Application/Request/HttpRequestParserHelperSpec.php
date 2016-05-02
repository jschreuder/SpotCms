<?php

namespace spec\Spot\Application\Request;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface as HttpRequest;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\Application\Request\ValidationFailedException;

/** @mixin  HttpRequestParserHelper */
class HttpRequestParserHelperSpec extends ObjectBehavior
{
    /** @var  HttpRequest */
    private $httpRequest;

    public function let(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
        $this->beConstructedWith($httpRequest);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(HttpRequestParserHelper::class);
    }

    public function it_can_provide_a_filter()
    {
        $this->getFilter()->shouldHaveType(Filter::class);
    }

    public function it_can_provide_a_validator()
    {
        $this->getValidator()->shouldHaveType(Validator::class);
    }
    
    public function it_can_execute_the_filter_and_validator()
    {
        $data = ['test' => '42', 'val' => '  too much space  '];
        $result = ['test' => 42, 'val' => 'too much space'];

        $this->getFilter()
            ->value('test')->int();
        $this->getFilter()
            ->value('val')->trim();
        $this->getValidator()
            ->required('test')->between(41, 43);
        $this->getValidator()
            ->required('val');
        $this->filterAndValidate($data)->shouldReturn($result);
    }

    public function it_can_execute_the_filter_and_validator_and_fail()
    {
        $data = ['test' => '42', 'val' => '  too much space  '];

        $this->getFilter()
            ->value('test')->int();
        $this->getFilter()
            ->value('val')->trim();
        $this->getValidator()
            ->required('test')->between(40, 41);
        $this->getValidator()
            ->required('val');
        $this->shouldThrow(ValidationFailedException::class)->duringFilterAndValidate($data);
    }
}
