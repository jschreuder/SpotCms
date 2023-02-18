<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\Validator\Uuid as UuidValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonView;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\Repository\PageRepository;

class DeletePageBlockController implements RequestValidatorInterface, ControllerInterface
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
            'page_block_uuid' => new UuidValidator(),
            'page_uuid' => new UuidValidator(),
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($query['page_uuid']));
        } catch (NoResultException $e) {
            return new JsonApiErrorResponse(['PAGE_NOT_FOUND' => 'Page not found'], 404);
        }

        try {
            $pageBlock = $page->getBlockByUuid(Uuid::fromString($query['page_block_uuid']));
        } catch (NoResultException $e) {
            return new JsonApiErrorResponse(['PAGE_BLOCK_NOT_FOUND' => 'PageBlock not found'], 404);
        }

        $this->pageRepository->deleteBlockFromPage($pageBlock, $page);
        return $this->renderer->render($request, new JsonView($pageBlock));
    }
}
