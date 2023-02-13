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
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Repository\PageRepository;

class GetPageController implements RequestValidatorInterface, ControllerInterface
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
            'page_uuid' => new UuidValidator(),
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($query['page_uuid']));
        } catch (NoUniqueResultException $e) {
            return new JsonApiErrorResponse(['PAGE_NOT_FOUND' => 'Page not found'], 404);
        }

        return $this->renderer->render($request, new JsonApiView($page, false, ['pageBlocks']));
    }
}
