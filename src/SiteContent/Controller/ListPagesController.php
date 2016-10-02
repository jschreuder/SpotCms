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
use Spot\Application\View\JsonApiView;
use Spot\SiteContent\Repository\PageRepository;

class ListPagesController implements RequestValidatorInterface, ControllerInterface
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
        $validator->optional('parent_uuid')->uuid();

        $result = $validator->validate($request->getQueryParams());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $parentUuid = !empty($request->getAttribute('parent_uuid'))
            ? Uuid::fromString($request->getAttribute('parent_uuid')) : null;

        return $this->renderer->render($request, new JsonApiView(
            $this->pageRepository->getAllByParentUuid($parentUuid),
            true,
            ['pageBlocks']
        ));
    }
}
