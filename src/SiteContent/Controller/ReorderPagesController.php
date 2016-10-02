<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Application\View\JsonApiView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;

class ReorderPagesController implements RequestValidatorInterface, ControllerInterface
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  RendererInterface */
    private $renderer;

    public function __construct(PageRepository $pageRepository, RendererInterface $renderer)
    {
        $this->pageRepository = $pageRepository;
        $this->renderer = $renderer;
    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $validator->required('data.ordered_pages')->isArray()
            ->each(function (Validator $validator) {
                $validator->required('page_uuid')->uuid();
            });

        $result = $validator->validate((array) $request->getParsedBody());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $data = $request->getParsedBody()['data'];
        $pages = $this->getPages(
            $data['ordered_pages'],
            Uuid::fromString($request->getAttribute('parent_uuid') ?: Uuid::NIL)
        );
        $pageSortOrders = $this->getPageSortOrders($pages);
        foreach ($pages as $idx => $page) {
            $page->setSortOrder($pageSortOrders[$idx]);
            $this->pageRepository->update($page);
        }

        return $this->renderer->render($request, new JsonApiView($pages, true));
    }

    /**
     * @param   array[] $pageArrays
     * @param   UuidInterface $parentUuid
     * @return  Page[]
     */
    private function getPages(array $pageArrays, UuidInterface $parentUuid) : array
    {
        $pages = [];
        foreach ($pageArrays as $pageArray) {
            $pages[] = $page = $this->pageRepository->getByUuid(Uuid::fromString($pageArray['page_uuid']));
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
