<?php

namespace spec\Spot\SiteContent\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\View\JsonView;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\HtmlContentBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Controller\UpdatePageBlockController;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  UpdatePageBlockController */
class UpdatePageBlockControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(UpdatePageBlockController::class);
    }

    public function it_can_validate_a_request(ServerRequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $pageUuid = Uuid::uuid4();
        $query = [
            'page_block_uuid' => $uuid->toString(),
            'page_uuid' => $pageUuid->toString(),
        ];
        $request->getQueryParams()->willReturn($query);
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'id' => $uuid->toString(),
                'attributes' => [
                    'parameters' => ['thx' => 1138],
                    'sort_order' => 42,
                    'status' => 'published',
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $this->validateRequest($request);
    }

    public function it_will_error_on_invalid_request_data(ServerRequestInterface $request)
    {
        $pageBlockUuid = Uuid::uuid4();
        $pageUuid = Uuid::uuid4();
        $query = [
            'page_block_uuid' => $pageBlockUuid->toString(),
            'page_uuid' => $pageUuid->toString(),
        ];
        $request->getQueryParams()->willReturn($query);
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'parameters' => [],
                    'sort_order' => '42',
                    'status' => 'nope',
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Page $page, PageBlock $pageBlock, ResponseInterface $response)
    {
        $pageUuid = Uuid::uuid4();
        $pageBlockUuid = Uuid::uuid4();
        $query = [
            'page_block_uuid' => $pageBlockUuid->toString(),
            'page_uuid' => $pageUuid->toString(),
        ];
        $request->getQueryParams()->willReturn($query);

        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'parameters' => ['content' => 42, 'new_gods' => 7],
                    'sort_order' => 2,
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $pageBlock->offsetSet('content', $post['data']['attributes']['parameters']['content'])->shouldBeCalled();
        $pageBlock->offsetSet('new_gods', $post['data']['attributes']['parameters']['new_gods'])->shouldBeCalled();
        $pageBlock->getType()->willReturn($post['data']['type']);
        $pageBlock->getParameters()->willReturn($post['data']['attributes']['parameters']);
        $pageBlock->setSortOrder($post['data']['attributes']['sort_order'])->shouldBeCalled();

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($pageBlockUuid)->willReturn($pageBlock);

        $this->blockTypeContainer->getType($post['data']['type'])
            ->willReturn(new HtmlContentBlockType());

        $this->pageRepository->updateBlockForPage($pageBlock, $page)->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_a_request_part_deux(ServerRequestInterface $request, Page $page, PageBlock $pageBlock, ResponseInterface $response)
    {
        $pageUuid = Uuid::uuid4();
        $pageBlockUuid = Uuid::uuid4();
        $query = [
            'page_block_uuid' => $pageBlockUuid->toString(),
            'page_uuid' => $pageUuid->toString(),
        ];
        $request->getQueryParams()->willReturn($query);

        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'status' => PageStatusValue::DELETED,
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $pageBlock->getType()->willReturn($post['data']['type']);
        $pageBlock->setStatus(PageStatusValue::get($post['data']['attributes']['status']))->shouldBeCalled();

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($pageBlockUuid)->willReturn($pageBlock);
        $pageBlock->getParameters()->willReturn(['content' => 'test']);

        $this->blockTypeContainer->getType($post['data']['type'])
            ->willReturn(new HtmlContentBlockType());

        $this->pageRepository->updateBlockForPage($pageBlock, $page)->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);
        
        $this->execute($request)->shouldReturn($response);
    }
}
