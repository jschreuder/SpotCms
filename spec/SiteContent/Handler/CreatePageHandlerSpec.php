<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Handler\CreatePageHandler;

/** @mixin  CreatePageHandler */
class CreatePageHandlerSpec extends ObjectBehavior
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

    public function it_is_initializable()
    {
        $this->shouldHaveType(CreatePageHandler::class);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     */
    public function it_canParseHttpRequest($httpRequest)
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
        $request->getRequestName()->shouldReturn(CreatePageHandler::MESSAGE);
        $request->getAttributes()->shouldBe($post['data']['attributes']);
    }

    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $httpRequest
     */
    public function it_errorsOnInvalidUuidWhenParsingRequest($httpRequest)
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

    /**
     * @param  \Spot\Api\Request\RequestInterface $request
     */
    public function it_canExecuteARequest($request)
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

    /**
     * @param  \Spot\Api\Request\RequestInterface $request
     */
    public function it_willThrowResponseExceptionOnErrors($request)
    {
        $request->offsetGet('title')->willThrow(new \OutOfBoundsException());
        $request->getAcceptContentType()->willReturn('application/json');
        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
