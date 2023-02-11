<?php

namespace spec\Spot\SiteContent\Controller;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Controller\CreatePageController;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  CreatePageController */
class CreatePageControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(CreatePageController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title',
                    'slug' => 'long-title',
                    'short_title' => 'Title',
                    'parent_uuid' => null,
                    'sort_order' => 42,
                    'status' => 'concept',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $request = $this->parseHttpRequest($httpRequest, []);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(CreatePageController::MESSAGE);
        $request->getAttributes()->shouldBe($post['data']['attributes']);
    }

    public function it_errors_on_invalid_data_in_request(ServerRequestInterface $httpRequest)
    {
        $post = [
            'data' => [
                'type' => 'pages',
                'attributes' => [
                    'title' => 'Long title',
                    'slug' => 'long_title',
                    'short_title' => '',
                    'parent_uuid' => null,
                    'sort_order' => 42,
                    'status' => 'concept',
                ],
            ]
        ];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, []);
    }

    public function it_can_execute_a_request(RequestInterface $request)
    {
        $request->offsetGet('title')->willReturn('Long title');
        $request->offsetGet('slug')->willReturn('long-title');
        $request->offsetGet('short_title')->willReturn('Title');
        $request->offsetGet('parent_uuid')->willReturn(null);
        $request->offsetGet('sort_order')->willReturn(42);
        $request->offsetGet('status')->willReturn('concept');
        $request->getAcceptContentType()->willReturn('application/json');

        $this->pageRepository->create(new Argument\Token\TypeToken(Page::class));

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response['data']->shouldHaveType(Page::class);
    }

    public function it_will_throw_ResponseException_on_errors(RequestInterface $request)
    {
        $request->offsetGet('title')->willThrow(new \OutOfBoundsException());
        $request->getAcceptContentType()->willReturn('application/json');
        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
