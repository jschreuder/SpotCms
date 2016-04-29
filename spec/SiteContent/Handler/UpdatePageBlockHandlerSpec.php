<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Handler\UpdatePageBlockHandler;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  UpdatePageBlockHandler */
class UpdatePageBlockHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(UpdatePageBlockHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $pageUuid = Uuid::uuid4();
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'id' => $uuid->toString(),
                'attributes' => [
                    'parameters' => ['thx' => 1138],
                    'sort_order' => 42,
                    'status' => 'published',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $request = $this->parseHttpRequest($httpRequest, [
            'uuid' => $uuid->toString(),
            'page_uuid' => $pageUuid->toString(),
        ]);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(UpdatePageBlockHandler::MESSAGE);
        $request->getAttributes()->shouldBe([
            'page_uuid' => $pageUuid->toString(),
            'parameters' => ['thx' => 1138],
            'sort_order' => 42,
            'status' => 'published',
            'uuid' => $uuid->toString(),
        ]);
    }

    public function it_will_error_on_invalid_request_data(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $pageUuid = Uuid::uuid4();
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'id' => $uuid->toString(),
                'attributes' => [
                    'parameters' => [],
                    'sort_order' => '42',
                    'status' => 'nope',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)
            ->duringParseHttpRequest($httpRequest, [
                'uuid' => $uuid->toString(),
                'page_uuid' => $pageUuid->toString(),
            ]);
    }
}
