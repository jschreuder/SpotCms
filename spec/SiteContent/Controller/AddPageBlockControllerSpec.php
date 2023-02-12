<?php

namespace spec\Spot\SiteContent\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\HtmlContentBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Controller\AddPageBlockController;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  AddPageBlockController */
class AddPageBlockControllerSpec extends ObjectBehavior
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  BlockTypeContainer */
    private $blockTypeContainer;

    /** @var  RendererInterface */
    private $renderer;

    public function let(PageRepository $pageRepository, BlockTypeContainer $container, RendererInterface $renderer)
    {
        $this->pageRepository = $pageRepository;
        $this->blockTypeContainer = $container;
        $this->renderer = $renderer;
        $this->beConstructedWith($pageRepository, $container, $renderer);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AddPageBlockController::class);
    }

    public function it_can_filter_a_request(ServerRequestInterface $request, ServerRequestInterface $request2, ServerRequestInterface $request3)
    {
        $query = ['page_uuid' => Uuid::uuid4()->toString()];
        $request->getQueryParams()->willReturn($query);
        $request->withQueryParams($query)->willReturn($request2);
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
            ],
        ];
        $request2->getParsedBody()->willReturn($post);
        $request2->withParsedBody($post)->willReturn($request3);

        $this->filterRequest($request)->shouldReturn($request3);
    }

    public function it_errors_on_invalid_uuid_when_validating_request(ServerRequestInterface $request)
    {
        $query = ['page_uuid' => 'nope'];
        $request->getQueryParams()->willReturn($query);
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
        $request->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Page $page, ResponseInterface $response)
    {
        $uuid = Uuid::uuid4();
        $query = ['page_uuid' => $uuid->toString()];
        $request->getQueryParams()->willReturn($query);
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'type' => 'fakeblock',
                    'parameters' => ['thx' => 1138],
                    'location' => 'main',
                    'sort_order' => 42,
                    'status' => PageStatusValue::CONCEPT,
                ],
            ]
        ];
        $request->getParsedBody()->willReturn($post);

        $this->pageRepository->getByUuid($uuid)
            ->willReturn($page);

        $this->blockTypeContainer->getType('fakeblock')
            ->willReturn(new HtmlContentBlockType());

        $this->pageRepository->addBlockToPage(new Argument\Token\TypeToken(PageBlock::class), $page)
            ->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonApiView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_a_page_not_found_request(ServerRequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $query = ['page_uuid' => $uuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getByUuid($uuid)
            ->willThrow(new NoUniqueResultException());

        $response = $this->execute($request);
        $response->shouldHaveType(JsonApiErrorResponse::class);
    }
}
