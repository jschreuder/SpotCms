<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\Validator\Uuid as UuidValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonView;
use Spot\SiteContent\Repository\PageRepository;

class ListPagesController implements RequestValidatorInterface, ControllerInterface
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
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $parentUuid = !empty($query['parent_uuid'])
            ? Uuid::fromString($query['parent_uuid']) : null;

        return $this->renderer->render($request, new JsonView(
            $this->pageRepository->getAllByParentUuid($parentUuid),
            true,
            ['pageBlocks']
        ));
    }
}
