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
use Spot\Application\View\JsonView;
use Spot\DataModel\Repository\NoResultException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Controller\GetPageBlockController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  GetPageBlockController */
class GetPageBlockControllerSpec extends ObjectBehavior
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  RendererInterface */
    private $renderer;

    public function let(PageRepository $pageRepository, RendererInterface $renderer)
    {
        $this->pageRepository = $pageRepository;
        $this->renderer = $renderer;
        $this->beConstructedWith($pageRepository, $renderer);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GetPageBlockController::class);
    }

    public function it_can_validate_a_request(ServerRequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();
        $query = ['page_block_uuid' => $blockUuid->toString(), 'page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->validateRequest($request);
    }

    public function it_errors_on_invalid_uuid_when_validating_request(ServerRequestInterface $request)
    {
        $blockUuid = Uuid::uuid4();
        $query = ['page_block_uuid' => $blockUuid->toString(), 'page_uuid' => 'nope'];
        $request->getQueryParams()->willReturn($query);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Page $page, PageBlock $block, ResponseInterface $response)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();
        $query = ['page_block_uuid' => $blockUuid->toString(), 'page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($blockUuid)->willReturn($block);

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_a_page_not_found_request(ServerRequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getByUuid($pageUuid)
            ->willThrow(new NoUniqueResultException());

        $this->execute($request)->shouldHaveType(JsonApiErrorResponse::class);
    }

    public function it_can_execute_a_block_not_found_request(ServerRequestInterface $request, Page $page)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();
        $query = ['page_block_uuid' => $blockUuid->toString(), 'page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($blockUuid)->willThrow(new NoResultException());

        $this->execute($request)->shouldHaveType(JsonApiErrorResponse::class);
    }
}
