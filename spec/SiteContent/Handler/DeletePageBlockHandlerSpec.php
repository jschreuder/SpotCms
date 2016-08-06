<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\DataModel\Repository\NoResultException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Handler\DeletePageBlockHandler;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  DeletePageBlockHandler */
class DeletePageBlockHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(DeletePageBlockHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();
        $attributes = ['uuid' => $blockUuid->toString(), 'page_uuid' => $pageUuid->toString()];

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(DeletePageBlockHandler::MESSAGE);
        $request['uuid']->shouldBe($attributes['uuid']);
        $request['page_uuid']->shouldBe($attributes['page_uuid']);
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $blockUuid = Uuid::uuid4();
        $attributes = ['uuid' => $blockUuid->toString(), 'page_uuid' => 'nope'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, Page $page, PageBlock $block)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();

        $request->offsetGet('uuid')->willReturn($blockUuid->toString());
        $request->offsetGet('page_uuid')->willReturn($pageUuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($blockUuid)->willReturn($block);
        $this->pageRepository->deleteBlockFromPage($block, $page)->shouldBeCalled();

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(DeletePageBlockHandler::MESSAGE);
        $response['data']->shouldBe($block);
    }

    public function it_can_execute_a_page_not_found_request(RequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('page_uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)
            ->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    public function it_can_execute_a_block_not_found_request(RequestInterface $request, Page $page)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();

        $request->offsetGet('uuid')->willReturn($blockUuid->toString());
        $request->offsetGet('page_uuid')->willReturn($pageUuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->getBlockByUuid($blockUuid)->willThrow(new NoResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $blockUuid = Uuid::uuid4();

        $request->offsetGet('uuid')->willReturn($blockUuid->toString());
        $request->offsetGet('page_uuid')->willReturn($pageUuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
