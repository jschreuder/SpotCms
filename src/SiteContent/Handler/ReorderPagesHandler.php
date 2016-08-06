<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Particle\Validator\Validator;
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

        $validator = $rpHelper->getValidator();
        $validator->required('data.ordered_pages')->isArray()
            ->each(function (Validator $validator) {
                $validator->required('uuid')->uuid();
            });

        return new Request(
            self::MESSAGE,
            $rpHelper->filterAndValidate((array) $httpRequest->getParsedBody())['data'],
            $httpRequest
        );
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        $pages = $this->getPages($request['ordered_pages']);
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
     * @param   array[] $pageArrays
     * @return  Page[]
     */
    private function getPages(array $pageArrays) : array
    {
        $pages = [];
        foreach ($pageArrays as $pageArray) {
            $pages[] = $this->pageRepository->getByUuid(Uuid::fromString($pageArray['uuid']));
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
