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
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\GetPageController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  GetPageController */
class GetPageControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(GetPageController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->validateRequest($request);
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $request)
    {
        $query = ['page_uuid' => 'nope'];
        $request->getQueryParams()->willReturn($query);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Page $page, ResponseInterface $response)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_a_not_found_request(ServerRequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $query = ['page_uuid' => $pageUuid->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->pageRepository->getByUuid($pageUuid)->willThrow(new NoUniqueResultException());

        $this->execute($request)->shouldHaveType(JsonApiErrorResponse::class);
    }
}
