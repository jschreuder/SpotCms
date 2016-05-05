<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\HtmlContentBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Handler\UpdatePageBlockHandler;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  UpdatePageBlockHandler */
class UpdatePageBlockHandlerSpec extends ObjectBehavior
{
    /** @var  \Spot\SiteContent\Repository\PageRepository */
    private $pageRepository;

    /** @var  BlockTypeContainer */
    private $blockTypeContainer;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    public function let(PageRepository $pageRepository, BlockTypeContainer $container, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->blockTypeContainer = $container;
        $this->logger = $logger;
        $this->beConstructedWith($pageRepository, $container, $logger);
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

    public function it_can_execute_a_request(RequestInterface $request, Page $page, PageBlock $pageBlock)
    {
        $pageUuid = Uuid::uuid4();
        $pageBlockUuid = Uuid::uuid4();
        $parameters = ['content' => 42, 'new_gods' => 7];
        $sortOrder = 2;
        $request->offsetExists('page_uuid')->willReturn(true);
        $request->offsetGet('page_uuid')->willReturn($pageUuid->toString());
        $request->offsetExists('uuid')->willReturn(true);
        $request->offsetGet('uuid')->willReturn($pageBlockUuid->toString());
        $request->offsetExists('parameters')->willReturn(true);
        $request->offsetGet('parameters')->willReturn($parameters);
        $request->offsetExists('sort_order')->willReturn(true);
        $request->offsetGet('sort_order')->willReturn($sortOrder);
        $request->offsetExists('status')->willReturn(false);
        $request->getAcceptContentType()->willReturn('text/xml');

        $pageBlock->offsetSet('content', $parameters['content'])->shouldBeCalled();
        $pageBlock->offsetSet('new_gods', $parameters['new_gods'])->shouldBeCalled();
        $pageBlock->getType()->willReturn('type');
        $pageBlock->getParameters()->willReturn($parameters);
        $pageBlock->setSortOrder($sortOrder)->shouldBeCalled();

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($pageBlockUuid)->willReturn($pageBlock);

        $this->blockTypeContainer->getType('type')
            ->willReturn(new HtmlContentBlockType());

        $this->pageRepository->updateBlockForPage($pageBlock, $page)->shouldBeCalled();
        $response = $this->executeRequest($request);
        $response['data']->shouldBe($pageBlock);
    }

    public function it_can_execute_a_request_part_deux(RequestInterface $request, Page $page, PageBlock $pageBlock)
    {
        $pageUuid = Uuid::uuid4();
        $pageBlockUuid = Uuid::uuid4();
        $newStatus = PageStatusValue::get(PageStatusValue::DELETED);
        $request->offsetExists('page_uuid')->willReturn(true);
        $request->offsetGet('page_uuid')->willReturn($pageUuid->toString());
        $request->offsetExists('uuid')->willReturn(true);
        $request->offsetGet('uuid')->willReturn($pageBlockUuid->toString());
        $request->offsetExists('parameters')->willReturn(false);
        $request->offsetExists('sort_order')->willReturn(false);
        $request->offsetExists('status')->willReturn(true);
        $request->offsetGet('status')->willReturn($newStatus->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $pageBlock->getType()->willReturn('type');
        $pageBlock->setStatus($newStatus)->shouldBeCalled();

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($pageBlockUuid)->willReturn($pageBlock);
        $pageBlock->getParameters()->willReturn(['content' => 'test']);

        $this->blockTypeContainer->getType('type')
            ->willReturn(new HtmlContentBlockType());

        $this->pageRepository->updateBlockForPage($pageBlock, $page)->shouldBeCalled();
        $response = $this->executeRequest($request);
        $response['data']->shouldBe($pageBlock);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $request->offsetGet('page_uuid')->willReturn($pageUuid);
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willThrow(new \RuntimeException());
        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
