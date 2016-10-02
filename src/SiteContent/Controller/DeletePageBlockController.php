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
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\Repository\PageRepository;

class DeletePageBlockController implements RequestValidatorInterface, ControllerInterface
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
        $validator->required('page_block_uuid')->uuid();
        $validator->required('page_uuid')->uuid();

        $result = $validator->validate($request->getAttributes());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getAttribute('page_uuid')));
        } catch (NoResultException $e) {
            return new JsonApiErrorResponse(['PAGE_NOT_FOUND' => 'Page not found'], 404);
        }

        try {
            $pageBlock = $page->getBlockByUuid(Uuid::fromString($request->getAttribute('page_block_uuid')));
        } catch (NoResultException $e) {
            return new JsonApiErrorResponse(['PAGE_BLOCK_NOT_FOUND' => 'PageBlock not found'], 404);
        }

        $this->pageRepository->deleteBlockFromPage($pageBlock, $page);
        return $this->renderer->render($request, new JsonApiView($pageBlock));
    }
}
