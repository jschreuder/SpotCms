<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\SiteContent\Repository\PageRepository;

class ReorderPagesHandler implements HttpRequestParserInterface, ExecutorInterface
{
    const MESSAGE = 'pages.reorder';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $rpHelper->getValidator()
            ->required('data.ordered_page_uuids.*')->uuid();

        return new Request(
            self::MESSAGE,
            $rpHelper->filterAndValidate((array) $httpRequest->getParsedBody())['data'],
            $httpRequest
        );
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        $orderedPageUuids = $request['ordered_page_uuids'];
        exit(var_dump($orderedPageUuids));
    }
}
