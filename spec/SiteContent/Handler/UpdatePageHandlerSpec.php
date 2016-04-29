<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Handler\UpdatePageHandler;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  UpdatePageHandler */
class UpdatePageHandlerSpec extends ObjectBehavior
{
    /** @var  \Spot\SiteContent\Repository\PageRepository */
    private $pageRepository;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    public function let(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
        $this->beConstructedWith($pageRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdatePageHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $post = [
            'data' => [
                'type' => 'pages',
                'id' => $uuid->toString(),
                'attributes' => [
                    'title' => 'Long title ',
                    'slug' => 'long-title',
                    'short_title' => ' Title',
                    'sort_order' => '42',
                    'status' => 'published',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $request = $this->parseHttpRequest($httpRequest, ['uuid' => $uuid->toString()]);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(UpdatePageHandler::MESSAGE);
        $request->getAttributes()->shouldBe([
            'title' => 'Long title',
            'slug' => 'long-title',
            'short_title' => 'Title',
            'sort_order' => 42,
            'status' => 'published',
            'uuid' => $uuid->toString(),
        ]);
    }

    public function it_will_error_on_invalid_request_data(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $post = [
            'data' => [
                'type' => 'pages',
                'id' => $uuid->toString(),
                'attributes' => [
                    'title' => 'Long title ',
                    'slug' => 'long-title',
                    'short_title' => ' Title',
                    'sort_order' => 'joe',
                    'status' => 'nonsense',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)
            ->duringParseHttpRequest($httpRequest, ['uuid' => $uuid->toString()]);
    }
}
