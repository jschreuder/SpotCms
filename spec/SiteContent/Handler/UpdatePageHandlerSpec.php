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
use Spot\SiteContent\Controller\UpdatePageController;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  UpdatePageController */
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
        $this->shouldHaveType(UpdatePageController::class);
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
        $request->getRequestName()->shouldReturn(UpdatePageController::MESSAGE);
        $request->getAttributes()->shouldBe([
            'type' => 'pages',
            'id' => $uuid->toString(),
            'attributes' => [
                'title' => 'Long title',
                'slug' => 'long-title',
                'short_title' => 'Title',
                'sort_order' => 42,
                'status' => 'published',
            ],
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
        $attributes = [
            'title' => $title = 'New Title',
            'slug' => $slug = 'new-title',
            'short_title' => $shortTitle = 'Title',
        ];
        $request->offsetGet('id')->willReturn($pageUuid->toString());
        $request->offsetGet('attributes')->willReturn($attributes);
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
        $attributes = [
            'sort_order' => $sortOrder = 3,
            'status' => PageStatusValue::CONCEPT,
        ];
        $request->offsetGet('id')->willReturn($pageUuid->toString());
        $request->offsetGet('attributes')->willReturn($attributes);
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willReturn($page);
        $page->setSortOrder($sortOrder)->shouldBeCalled();
        $page->setStatus(PageStatusValue::get($attributes['status']))->shouldBeCalled();

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
