<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\HtmlContentBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Controller\AddPageBlockController;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  AddPageBlockController */
class AddPageBlockHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(AddPageBlockController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'type' => 'type',
                    'parameters' => ['content' => '1138'],
                    'location' => 'main',
                    'sort_order' => 42,
                    'status' => 'published',
                ],
            ]
        ];
        $attributes = ['page_uuid' => Uuid::uuid4()->toString()];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(AddPageBlockController::MESSAGE);
        $request->getAttributes()->shouldBe(array_merge(['id' => $attributes['page_uuid']], $post['data']));
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'type' => 'type',
                    'parameters' => ['thx' => 1138],
                    'location' => 'main',
                    'sort_order' => 42,
                    'status' => 'published',
                ],
            ]
        ];
        $attributes = ['page_uuid' => 'nope'];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, Page $page)
    {
        $uuid = Uuid::uuid4();
        $attributes = [
            'type' => 'type',
            'parameters' => ['content' => 1138],
            'location' => 'main',
            'sort_order' => 52,
            'status' => PageStatusValue::CONCEPT,
        ];
        $request->offsetGet('id')->willReturn($uuid->toString());
        $request->offsetGet('attributes')->willReturn($attributes);
        $request->getAcceptContentType()->willReturn('application/json');

        $this->pageRepository->getByUuid($uuid)
            ->willReturn($page);

        $this->blockTypeContainer->getType('type')
            ->willReturn(new HtmlContentBlockType());

        $this->pageRepository->addBlockToPage(new Argument\Token\TypeToken(PageBlock::class), $page)
            ->shouldBeCalled();

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response['data']->shouldHaveType(PageBlock::class);
        $response['includes']->shouldBe(['pages']);
    }

    public function it_can_execute_a_page_not_found_request(RequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('id')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)
            ->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $request->offsetGet('id')->willReturn($pageUuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
