<?php

namespace spec\Spot\SiteContent\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\View\JsonView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\ListPagesController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  ListPagesController */
class ListPagesControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(ListPagesController::class);
    }

    public function it_can_validate_a_request(ServerRequestInterface $request)
    {
        $parentUuid = Uuid::uuid4();
        $query = ['parent_uuid' => $parentUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->validateRequest($request);
    }

    public function it_errors_on_invalid_uuid_when_validating_request(ServerRequestInterface $request)
    {
        $query = ['parent_uuid' => 'nope'];
        $request->getQueryParams()->willReturn($query);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Page $page, ResponseInterface $response)
    {
        $parentUuid = Uuid::uuid4();
        $query = ['parent_uuid' => $parentUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getAllByParentUuid($parentUuid)->willReturn([$page]);

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }
}
