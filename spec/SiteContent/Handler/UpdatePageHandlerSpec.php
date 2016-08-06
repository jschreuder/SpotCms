<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Handler\UpdatePageHandler;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  UpdatePageHandler */
class UpdatePageHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(UpdatePageHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $post = [
            'data' => [
                'type' => 'pages',
                'id' => $uuid->toString(),
                'attributes' => [
                    'title' => 'Long title ',
                    'slug' => 'long-title',
                    'short_title' => ' Title',
                    'sort_order' => '42',
                    'status' => 'published',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $request = $this->parseHttpRequest($httpRequest, ['uuid' => $uuid->toString()]);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(UpdatePageHandler::MESSAGE);
        $request->getAttributes()->shouldBe([
            'title' => 'Long title',
            'slug' => 'long-title',
            'short_title' => 'Title',
            'sort_order' => 42,
            'status' => 'published',
            'uuid' => $uuid->toString(),
        ]);
    }

    public function it_will_error_on_invalid_request_data(ServerRequestInterface $httpRequest)
    {
        $uuid = Uuid::uuid4();
        $post = [
            'data' => [
                'type' => 'pages',
                'id' => $uuid->toString(),
                'attributes' => [
                    'title' => 'Long title ',
                    'slug' => 'long-title',
                    'short_title' => ' Title',
                    'sort_order' => 'joe',
                    'status' => 'nonsense',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)
            ->duringParseHttpRequest($httpRequest, ['uuid' => $uuid->toString()]);
    }

    public function it_can_execute_a_request(RequestInterface $request, Page $page)
    {
        $pageUuid = Uuid::uuid4();
        $title = 'New Title';
        $slug = 'new-title';
        $shortTitle = 'Title';
        $request->offsetGet('uuid')->willReturn($pageUuid->toString());
        $request->offsetExists('title')->willReturn(true);
        $request->offsetGet('title')->willReturn($title);
        $request->offsetExists('slug')->willReturn(true);
        $request->offsetGet('slug')->willReturn($slug);
        $request->offsetExists('short_title')->willReturn(true);
        $request->offsetGet('short_title')->willReturn($shortTitle);
        $request->offsetExists('sort_order')->willReturn(false);
        $request->offsetExists('status')->willReturn(false);
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->setTitle($title)->shouldBeCalled();
        $page->setSlug($slug)->shouldBeCalled();
        $page->setShortTitle($shortTitle)->shouldBeCalled();

        $this->pageRepository->update($page)->shouldBeCalled();
        $response = $this->executeRequest($request);
        $response['data']->shouldBe($page);
    }

    public function it_can_execute_a_request_part_deux(RequestInterface $request, Page $page)
    {
        $pageUuid = Uuid::uuid4();
        $sortOrder = 3;
        $status = PageStatusValue::get(PageStatusValue::CONCEPT);
        $request->offsetGet('uuid')->willReturn($pageUuid->toString());
        $request->offsetExists('title')->willReturn(false);
        $request->offsetExists('slug')->willReturn(false);
        $request->offsetExists('short_title')->willReturn(false);
        $request->offsetExists('sort_order')->willReturn(true);
        $request->offsetGet('sort_order')->willReturn($sortOrder);
        $request->offsetExists('status')->willReturn(true);
        $request->offsetGet('status')->willReturn($status->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->setSortOrder($sortOrder)->shouldBeCalled();
        $page->setStatus($status)->shouldBeCalled();

        $this->pageRepository->update($page)->shouldBeCalled();
        $response = $this->executeRequest($request);
        $response['data']->shouldBe($page);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $request->offsetGet('uuid')->willReturn($pageUuid);
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willThrow(new \RuntimeException());
        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
