<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
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
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Handler\AddPageBlockHandler;
use Spot\SiteContent\Repository\PageRepository;

/** @mixin  AddPageBlockHandler */
class AddPageBlockHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(AddPageBlockHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'type' => 'type',
                    'parameters' => ['thx' => 1138],
                    'location' => 'main',
                    'sort_order' => 42,
                    'status' => 'published',
                ],
            ]
        ];
        $attributes = ['page_uuid' => Uuid::uuid4()->toString()];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(AddPageBlockHandler::MESSAGE);
        $request->getAttributes()->shouldBe(array_merge($attributes, $post['data']['attributes']));
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $post = [
            'data' => [
                'type' => 'pageBlocks',
                'attributes' => [
                    'type' => 'type',
                    'parameters' => ['thx' => 1138],
                    'location' => 'main',
                    'sort_order' => 42,
                    'status' => 'published',
                ],
            ]
        ];
        $attributes = ['page_uuid' => 'nope'];
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');
        $httpRequest->getParsedBody()->willReturn($post);

        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, Page $page)
    {
        $uuid = Uuid::uuid4();
        $request->offsetGet('page_uuid')->willReturn($uuid->toString());
        $request->offsetGet('type')->willReturn('type');
        $request->offsetGet('parameters')->willReturn(['thx' => 1138]);
        $request->offsetGet('location')->willReturn('main');
        $request->offsetGet('sort_order')->willReturn(42);
        $request->offsetGet('status')->willReturn('concept');
        $request->getAcceptContentType()->willReturn('application/json');

        $this->pageRepository->getByUuid($uuid)
            ->willReturn($page);

        $this->pageRepository->addBlockToPage(new Argument\Token\TypeToken(PageBlock::class), $page)
            ->shouldBeCalled();

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response['data']->shouldHaveType(PageBlock::class);
        $response['includes']->shouldBe(['pages']);
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

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $pageUuid = Uuid::uuid4();
        $request->offsetGet('page_uuid')->willReturn($pageUuid->toString());
        $request->getAcceptContentType()->willReturn('text/xml');

        $this->pageRepository->getByUuid($pageUuid)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
