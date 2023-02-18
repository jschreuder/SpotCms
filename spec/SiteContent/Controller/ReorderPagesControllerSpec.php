<?php

namespace spec\Spot\SiteContent\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use OutOfBoundsException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\View\JsonView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\ReorderPagesController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin ReorderPagesController */
class ReorderPagesControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(ReorderPagesController::class);
    }

    public function it_can_validate_request(ServerRequestInterface $request)
    {
        $body = [
            'data' => [
                'ordered_pages' => [
                    Uuid::uuid4()->toString(),
                    Uuid::uuid4()->toString(),
                    Uuid::uuid4()->toString(),
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($body);
        $query = ['parent_uuid' => Uuid::uuid4()->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->validateRequest($request);
    }

    public function it_errors_on_invalid_uuid_when_validating_request(ServerRequestInterface $request)
    {
        $body = [
            'data' => [
                'ordered_pages' => [
                    1, 2, 3
                ],
            ],
        ];
        $request->getParsedBody()->willReturn($body);
        $query = ['parent_uuid' => Uuid::uuid4()->toString()];
        $request->getQueryParams()->willReturn($query);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_request(ServerRequestInterface $request, Page $page1, Page $page2, Page $page3, ResponseInterface $response)
    {
        $parentUuid = Uuid::uuid4();
        $page1Uuid = Uuid::uuid4();
        $page1->getUuid()->willReturn($page1Uuid);
        $page1->getParentUuid()->willReturn($parentUuid);
        $page1->getSortOrder()->willReturn(1);
        $page1->setSortOrder(3)->shouldBeCalled();
        $page2Uuid = Uuid::uuid4();
        $page2->getUuid()->willReturn($page2Uuid);
        $page2->getParentUuid()->willReturn($parentUuid);
        $page2->getSortOrder()->willReturn(2);
        $page2->setSortOrder(1)->shouldBeCalled();
        $page3Uuid = Uuid::uuid4();
        $page3->getUuid()->willReturn($page3Uuid);
        $page3->getParentUuid()->willReturn($parentUuid);
        $page3->getSortOrder()->willReturn(3);
        $page3->setSortOrder(2)->shouldBeCalled();

        $this->pageRepository->getByUuid($page1Uuid)->willReturn($page1);
        $this->pageRepository->getByUuid($page2Uuid)->willReturn($page2);
        $this->pageRepository->getByUuid($page3Uuid)->willReturn($page3);

        $this->pageRepository->update($page1)->shouldBeCalled();
        $this->pageRepository->update($page2)->shouldBeCalled();
        $this->pageRepository->update($page3)->shouldBeCalled();

        $orderedPageUuids = [
            $page2Uuid->toString(),
            $page3Uuid->toString(),
            $page1Uuid->toString(),
        ];
        $request->getParsedBody()->willReturn(['data' => ['ordered_pages' => $orderedPageUuids]]);
        $request->getQueryParams()->willReturn(['parent_uuid' => $parentUuid->toString()]);

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_request_with_null_parent(ServerRequestInterface $request, Page $page1, Page $page2, ResponseInterface $response)
    {
        $parentUuid = null;
        $page1Uuid = Uuid::uuid4();
        $page1->getUuid()->willReturn($page1Uuid);
        $page1->getParentUuid()->willReturn(null);
        $page1->getSortOrder()->willReturn(1);
        $page1->setSortOrder(2)->shouldBeCalled();
        $page2Uuid = Uuid::uuid4();
        $page2->getUuid()->willReturn($page2Uuid);
        $page2->getParentUuid()->willReturn(null);
        $page2->getSortOrder()->willReturn(2);
        $page2->setSortOrder(1)->shouldBeCalled();

        $this->pageRepository->getByUuid($page1Uuid)->willReturn($page1);
        $this->pageRepository->getByUuid($page2Uuid)->willReturn($page2);

        $this->pageRepository->update($page1)->shouldBeCalled();
        $this->pageRepository->update($page2)->shouldBeCalled();

        $orderedPageUuids = [
            $page2Uuid->toString(),
            $page1Uuid->toString(),
        ];
        $request->getParsedBody()->willReturn(['data' => ['ordered_pages' => $orderedPageUuids]]);
        $request->getQueryParams()->willReturn(['parent_uuid' => $parentUuid]);

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }

    public function it_errors_when_parent_doesnt_match_page(ServerRequestInterface $request, Page $page1)
    {
        $parentUuid = Uuid::uuid4();
        $page1Uuid = Uuid::uuid4();
        $page1->getUuid()->willReturn($page1Uuid);
        $page1->getParentUuid()->willReturn(Uuid::uuid4());
        $page1->getSortOrder()->willReturn(1);

        $this->pageRepository->getByUuid($page1Uuid)->willReturn($page1);

        $orderedPageUuids = [
            $page1Uuid->toString(),
        ];
        $request->getParsedBody()->willReturn(['data' => ['ordered_pages' => $orderedPageUuids]]);
        $request->getQueryParams()->willReturn(['parent_uuid' => $parentUuid->toString()]);

        $this->shouldThrow(OutOfBoundsException::class)->duringExecute($request);
    }
}
