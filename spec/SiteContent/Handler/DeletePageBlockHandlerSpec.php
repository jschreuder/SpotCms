<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\ResponseException;
use Spot\Application\Request\ValidationFailedException;
use Spot\DataModel\Repository\NoResultException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Handler\DeletePageBlockHandler;

/** @mixin  DeletePageBlockHandler */
class DeletePageBlockHandlerSpec extends ObjectBehavior
{
    /** @var  \Spot\SiteContent\Repository\PageRepository */
    private $pageRepository;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \Spot\SiteContent\Repository\PageRepository $pageRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function let($pageRepository, $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
        $this->beConstructedWith($pageRepository, $logger);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(DeletePageBlockHandler::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     */
    public function it_canParseHttpRequest($httpRequest)
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

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     */
    public function it_errorsOnInvalidUuidWhenParsingRequest($httpRequest)
    {
        $blockUuid = Uuid::uuid4();
        $attributes = ['uuid' => $blockUuid->toString(), 'page_uuid' => 'nope'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Spot\SiteContent\Entity\Page $page
     * @param  \Spot\SiteContent\Entity\PageBlock $block
     */
    public function it_canExecuteARequest($request, $page, $block)
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

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_canExecuteAPageNotFoundRequest($request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('page_uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)
            ->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function it_canExecuteABlockNotFoundRequest($request, $page)
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

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_canHandleExceptionDuringRequest($request)
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
