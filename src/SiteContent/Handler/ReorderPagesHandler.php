<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
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
                $validator->required('page_uuid')->uuid();
            });
        $validator->required('data.parent_uuid')->uuid();

        $data = (array) $httpRequest->getParsedBody();
        $data['data']['parent_uuid'] = $attributes['parent_uuid'];

        return new Request(self::MESSAGE, $rpHelper->filterAndValidate($data)['data'], $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $pages = $this->getPages(Uuid::fromString($request['parent_uuid']), $request['ordered_pages']);
            $pageSortOrders = $this->getPageSortOrders($pages);
            foreach ($pages as $idx => $page) {
                $page->setSortOrder($pageSortOrders[$idx]);
                $this->pageRepository->update($page);
            }
            return new Response(self::MESSAGE, [
                'data' => $pages,
            ], $request);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during ReorderPagesHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    /**
     * @param   array[] $pageArrays
     * @return  Page[]
     */
    private function getPages(UuidInterface $uuid, array $pageArrays) : array
    {
        if ($uuid->toString() === Uuid::NIL) {
            $uuid = null;
        }

        $pages = [];
        foreach ($pageArrays as $pageArray) {
            $pages[] = $page = $this->pageRepository->getByUuid(Uuid::fromString($pageArray['uuid']));
            if (!(is_null($page->getParentUuid()) && is_null($uuid)) && !$page->getParentUuid()->equals($uuid)) {
                throw new \OutOfBoundsException('All reordered pages must be children of the given parent page.');
            }
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
