<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Handler\ReorderPagesHandler;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin ReorderPagesHandler */
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
        $this->shouldHaveType(ReorderPagesHandler::class);
    }

    public function it_can_parse_httpRequest(ServerHttpRequest $httpRequest)
    {
        $body = [
            'data' => [
                'ordered_page_uuids' => [
                    Uuid::uuid4()->toString(),
                    Uuid::uuid4()->toString(),
                    Uuid::uuid4()->toString(),
                ],
            ],
        ];

        $httpRequest->getHeaderLine('Accept')->willReturn('*/*');
        $httpRequest->getParsedBody()->willReturn($body);

        $request = $this->parseHttpRequest($httpRequest, []);
        $request->shouldHaveType(RequestInterface::class);
        $request->getAttributes()->shouldBe($body['data']);
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerHttpRequest $httpRequest)
    {
        $httpRequest->getParsedBody()->willReturn(['nope']);
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, []);
    }

    public function it_can_execute_request(RequestInterface $request, Page $page1, Page $page2, Page $page3)
    {
        $page1Uuid = Uuid::uuid4();
        $page1->getUuid()->willReturn($page1Uuid);
        $page1->getSortOrder()->willReturn(1);
        $page1->setSortOrder(3)->shouldBeCalled();
        $page2Uuid = Uuid::uuid4();
        $page2->getUuid()->willReturn($page2Uuid);
        $page2->getSortOrder()->willReturn(2);
        $page2->setSortOrder(1)->shouldBeCalled();
        $page3Uuid = Uuid::uuid4();
        $page3->getUuid()->willReturn($page3Uuid);
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
        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('ordered_page_uuids')->willReturn($orderedPageUuids);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response['data']->shouldBe([$page2, $page3, $page1]);
    }
}
