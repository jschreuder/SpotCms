<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Handler\ListPagesHandler;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  ListPagesHandler */
class ListPagesHandlerSpec extends ObjectBehavior
{
    /** @var  \Spot\SiteContent\Repository\PageRepository */
    private $pageRepository;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    public function let(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
        $this->beConstructedWith($pageRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ListPagesHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $httpRequest->getQueryParams()->willReturn(['parent_uuid' => $uuid->toString()]);
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');

        $request = $this->parseHttpRequest($httpRequest, []);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(ListPagesHandler::MESSAGE);
        $request['parent_uuid']->shouldBe($uuid->toString());
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $httpRequest->getQueryParams()->willReturn(['parent_uuid' => 'nope']);
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, []);
    }

    public function it_can_execute_a_request(RequestInterface $request, Page $page)
    {
        $uuid = Uuid::uuid4();
        $request->offsetExists('parent_uuid')->willReturn(true);
        $request->offsetGet('parent_uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');
        $this->pageRepository->getAllByParentUuid(new Argument\Token\TypeToken(Uuid::class))
            ->willReturn([$page]);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(ListPagesHandler::MESSAGE);
        $response['data']->shouldBe([$page]);
        $response['parent_uuid']->toString()->shouldBe($uuid->toString());
        $response['includes']->shouldBe(['pageBlocks']);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetExists('parent_uuid')->willReturn(true);
        $request->offsetGet('parent_uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getAllByParentUuid($uuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
