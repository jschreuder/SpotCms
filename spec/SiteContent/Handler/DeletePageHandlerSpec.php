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
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Handler\DeletePageHandler;

/** @mixin  DeletePageHandler */
class DeletePageHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(DeletePageHandler::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     */
    public function it_canParseHttpRequest($httpRequest)
    {
        $uuid = Uuid::uuid4();
        $attributes = ['uuid' => $uuid->toString()];

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(DeletePageHandler::MESSAGE);
        $request['uuid']->shouldBe($attributes['uuid']);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     */
    public function it_errorsOnInvalidUuidWhenParsingRequest($httpRequest)
    {
        $attributes = ['uuid' => 'nope'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function it_canExecuteARequest($request, $page)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');
        $this->pageRepository->getByUuid($uuid)->willReturn($page);
        $this->pageRepository->delete($page)->shouldBeCalled();

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(DeletePageHandler::MESSAGE);
        $response['data']->shouldBe($page);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_canExecuteANotFoundRequest($request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    /**
     * @param  \Spot\Api\Request\Message\RequestInterface $request
     */
    public function it_canHandleExceptionDuringRequest($request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}