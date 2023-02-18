<?php

namespace spec\Spot\SiteContent\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\View\JsonView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\CreatePageController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  CreatePageController */
class CreatePageControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(CreatePageController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title',
                    'slug' => 'long-title',
                    'short_title' => 'Title',
                    'parent_uuid' => null,
                    'sort_order' => 42,
                    'status' => 'concept',
                ],
            ]
        ];
        $request->getParsedBody()->willReturn($post);
        $request->withParsedBody($post)->willReturn($request2);

        $this->filterRequest($request)->shouldReturn($request2);
    }

    public function it_errors_on_invalid_data_in_request(ServerRequestInterface $request)
    {
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title',
                    'slug' => 'long_title',
                    'short_title' => '',
                    'parent_uuid' => null,
                    'sort_order' => 42,
                    'status' => 'concept',
                ],
            ]
        ];
        $request->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, ResponseInterface $response)
    {
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title',
                    'slug' => 'long-title',
                    'short_title' => 'Title',
                    'parent_uuid' => null,
                    'sort_order' => 42,
                    'status' => 'concept',
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($post);

        $this->pageRepository->create(new Argument\Token\TypeToken(Page::class));
        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }
}
