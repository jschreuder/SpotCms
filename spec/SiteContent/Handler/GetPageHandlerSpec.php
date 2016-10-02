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
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\GetPageController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  GetPageController */
class GetPageHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(GetPageController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $attributes = ['uuid' => $uuid->toString()];

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(GetPageController::MESSAGE);
        $request['uuid']->shouldBe($attributes['uuid']);
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $attributes = ['uuid' => 'nope'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, Page $page)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');
        $this->pageRepository->getByUuid($uuid)->willReturn($page);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(GetPageController::MESSAGE);
        $response['data']->shouldBe($page);
        $response['includes']->shouldBe(['pageBlocks']);
    }

    public function it_can_execute_a_not_found_request(RequestInterface $request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    public function it_can_handle_exception_during_request(RequestInterface  $request)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($uuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($uuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
