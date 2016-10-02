<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\ReorderPagesController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin ReorderPagesController */
class ReorderPagesHandlerSpec extends ObjectBehavior
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  LoggerInterface */
    private $logger;

    public function let(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
        $this->beConstructedWith($pageRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ReorderPagesController::class);
    }

    public function it_can_parse_httpRequest(ServerHttpRequest $httpRequest)
    {
        $body = [
            'data' => [
                'ordered_pages' => [
                    ['page_uuid' => Uuid::uuid4()->toString()],
                    ['page_uuid' => Uuid::uuid4()->toString()],
                    ['page_uuid' => Uuid::uuid4()->toString()],
                ],
            ],
        ];
        $attributes = ['parent_uuid' => Uuid::uuid4()->toString()];

        $httpRequest->getHeaderLine('Accept')->willReturn('*/*');
        $httpRequest->getParsedBody()->willReturn($body);

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getAttributes()->shouldBe(array_merge($body['data'], $attributes));
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerHttpRequest $httpRequest)
    {
        $httpRequest->getParsedBody()->willReturn(['nope']);
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest(
            $httpRequest, ['parent_uuid' => Uuid::uuid4()->toString()]
        );
    }

    public function it_can_execute_request(RequestInterface $request, Page $page1, Page $page2, Page $page3)
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
            ['page_uuid' => $page2Uuid->toString()],
            ['page_uuid' => $page3Uuid->toString()],
            ['page_uuid' => $page1Uuid->toString()],
        ];
        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('ordered_pages')->willReturn($orderedPageUuids);
        $request->offsetGet('parent_uuid')->willReturn($parentUuid->toString());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response['data']->shouldBe([$page2, $page3, $page1]);
    }

    public function it_can_execute_request_with_null_parent(RequestInterface $request, Page $page1, Page $page2)
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
            ['page_uuid' => $page2Uuid->toString()],
            ['page_uuid' => $page1Uuid->toString()],
        ];
        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('ordered_pages')->willReturn($orderedPageUuids);
        $request->offsetGet('parent_uuid')->willReturn($parentUuid);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response['data']->shouldBe([$page2, $page1]);
    }

    public function it_errors_when_parent_doesnt_match_page(RequestInterface $request, Page $page1)
    {
        $parentUuid = Uuid::uuid4();
        $page1Uuid = Uuid::uuid4();
        $page1->getUuid()->willReturn($page1Uuid);
        $page1->getParentUuid()->willReturn(Uuid::uuid4());
        $page1->getSortOrder()->willReturn(1);

        $this->pageRepository->getByUuid($page1Uuid)->willReturn($page1);

        $orderedPageUuids = [
            ['page_uuid' => $page1Uuid->toString()],
        ];
        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('ordered_pages')->willReturn($orderedPageUuids);
        $request->offsetGet('parent_uuid')->willReturn($parentUuid->toString());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('ordered_pages')->willReturn([
            ['page_uuid' => $uuid->toString()],
        ]);

        $this->pageRepository->getByUuid($uuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
