<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;

class ReorderPagesHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.reorder';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $rpHelper->getValidator()
            ->required('data.ordered_page_uuids')->isArray();

        return new Request(
            self::MESSAGE,
            $rpHelper->filterAndValidate((array) $httpRequest->getParsedBody())['data'],
            $httpRequest
        );
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        $pages = $this->getPages($request['ordered_page_uuids']);
        $pageSortOrders = $this->getPageSortOrders($pages);
        foreach ($pages as $idx => $page) {
            $page->setSortOrder($pageSortOrders[$idx]);
            $this->pageRepository->update($page);
        }
        return new Response(self::MESSAGE, [
            'data' => $pages,
        ], $request);
    }

    /**
     * @param   string[] $pageUuids
     * @return  Page[]
     */
    private function getPages(array $pageUuids) : array
    {
        $pages = [];
        foreach ($pageUuids as $pageUuid) {
            $pages[] = $this->pageRepository->getByUuid(Uuid::fromString($pageUuid));
        }
        return $pages;
    }

    /**
     * @param   Page[] $pages
     * @return  int[]
     */
    private function getPageSortOrders(array $pages) : array
    {
        $sortOrders = [];
        foreach ($pages as $page) {
            $sortOrders[] = $page->getSortOrder();
        }
        sort($sortOrders);
        return array_merge($sortOrders);
    }
}
