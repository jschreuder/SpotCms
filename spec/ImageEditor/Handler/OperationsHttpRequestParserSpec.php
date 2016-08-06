<?php

namespace spec\Spot\ImageEditor\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Api\Request\RequestInterface;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\Handler\OperationsHttpRequestParser;

/** @mixin  OperationsHttpRequestParser */
class OperationsHttpRequestParserSpec extends ObjectBehavior
{
    /** @var  FileManagerHelper */
    private $helper;

    /** @var  string */
    private $messageName = 'some.test';

    public function let()
    {
        $this->helper = new FileManagerHelper();
        $this->beConstructedWith($this->messageName, $this->helper);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OperationsHttpRequestParser::class);
    }

    public function it_can_parse_the_request(ServerRequestInterface $httpRequest)
    {
        $path = '/some/path/to/file.png';
        $data = [
            'operations' => [
                'resize' => [
                    'width' => 42,
                    'height' => 420,
                ],
                'crop' => [
                    'x' => 42,
                    'y' => 42,
                    'width' => 42,
                    'height' => 420,
                ],
                'rotate' => [
                    'degrees' => 180,
                ],
                'negative' => [
                    'apply' => true,
                ],
                'gamma' => [
                    'correction' => 1.9,
                ],
                'greyscale' => [
                    'apply' => true,
                ],
                'blur' => [
                    'amount' => 0.5,
                ],
            ],
        ];

        $httpRequest->getHeaderLine('Accept')->willReturn('image/png');
        $httpRequest->getQueryParams()->willReturn($data);

        $response = $this->parseHttpRequest($httpRequest, ['path' => $path]);
        $response->shouldHaveType(RequestInterface::class);
        $response->getRequestName()->shouldReturn($this->messageName);
        $response->offsetGet('operations')->shouldReturn($data['operations']);
        $response->offsetGet('path')->shouldReturn($path);
    }
}
