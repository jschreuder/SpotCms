<?php declare(strict_types = 1);

namespace Spot\FileManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\FilterService;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonApiView;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;

class GetDirectoryListingController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    public function __construct(
        private FileRepository $fileRepository,
        private FileManagerHelper $helper,
        private RendererInterface $renderer
    )
    {
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        return FilterService::filterQuery($request, [
            'path' => $this->helper->getPathFilter(),
        ]);
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, [
            'path' => $this->helper->getPathValidator(),
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $directories = $this->fileRepository->getDirectoriesInPath($query['path']);
        $fileNames = $this->fileRepository->getFileNamesInPath($query['path']);
        return $this->renderer->render($request, new JsonApiView([
            'path' => $query['path'],
            'directories' => $directories,
            'files' => $fileNames
        ]));
    }
}
