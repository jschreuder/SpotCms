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
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\UpdatePageController;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  UpdatePageController */
class UpdatePageControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(UpdatePageController::class);
    }

    public function it_can_validate_a_request(ServerRequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title ',
                    'slug' => 'long-title',
                    'short_title' => ' Title',
                    'sort_order' => '42',
                    'status' => 'published',
                ],
            ]
        ];
        $request->getParsedBody()->willReturn($post);

        $request = $this->validateRequest($request);
    }

    public function it_will_error_on_invalid_request_data(ServerRequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title ',
                    'slug' => 'long-title',
                    'short_title' => ' Title',
                    'sort_order' => 'joe',
                    'status' => 'nonsense',
                ],
            ]
        ];
        $request->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)
            ->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Page $page, ResponseInterface $response)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);
        $post = [
            'data' => [
                'attributes' => [
                    'title' => $title = 'New Title',
                    'slug' => $slug = 'new-title',
                    'short_title' => $shortTitle = 'Title',
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->setTitle($title)->shouldBeCalled();
        $page->setSlug($slug)->shouldBeCalled();
        $page->setShortTitle($shortTitle)->shouldBeCalled();

        $this->pageRepository->update($page)->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_a_request_part_deux(ServerRequestInterface $request, Page $page, ResponseInterface $response)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $post = [
            'data' => [
                'attributes' => [
                    'sort_order' => $sortOrder = 3,
                    'status' => PageStatusValue::CONCEPT,
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->setSortOrder($sortOrder)->shouldBeCalled();
        $page->setStatus(PageStatusValue::get($post['data']['attributes']['status']))->shouldBeCalled();

        $this->pageRepository->update($page)->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }
}
