<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\Validator\Uuid as UuidValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Application\ValidationService;
use Spot\Application\Validator\IsListOf;
use Spot\Application\View\JsonView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;

class ReorderPagesController implements RequestValidatorInterface, ControllerInterface
{
    public function __construct(
        private PageRepository $pageRepository,
        private RendererInterface $renderer
    )
    {
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, [
            'parent_uuid' => new UuidValidator(),
        ], ['parent_uuid']);
        ValidationService::validate($request, [
            'data.ordered_pages' => new IsListOf(function ($value) {
                return is_string($value) && Uuid::isValid($value);
            }),
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $parentUuid = $request->getQueryParams()['parent_uuid'] ?: Uuid::NIL;
        $orderedPages = $request->getParsedBody()['data']['ordered_pages'];
        $pages = $this->getPages(
            $orderedPages,
            Uuid::fromString($parentUuid)
        );
        $pageSortOrders = $this->getPageSortOrders($pages);
        foreach ($pages as $idx => $page) {
            $page->setSortOrder($pageSortOrders[$idx]);
            $this->pageRepository->update($page);
        }

        return $this->renderer->render($request, new JsonView($pages, true));
    }

    /**
     * @param   string[] $pageArrays
     * @return  Page[]
     */
    private function getPages(array $pageUuids, UuidInterface $parentUuid) : array
    {
        $pages = [];
        foreach ($pageUuids as $pageUuid) {
            $pages[] = $page = $this->pageRepository->getByUuid(Uuid::fromString($pageUuid));
            if (!$parentUuid->equals($page->getParentUuid() ?: Uuid::fromString(Uuid::NIL))) {
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
