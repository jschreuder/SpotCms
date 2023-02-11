<?php declare(strict_types = 1);

namespace Spot\FileManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\FilterService;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;

class RenameFileController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
        $request = FilterService::filterQuery($request, [
            'path' => $this->helper->getPathFilter(),
        ]);
        return FilterService::filter($request, [
            'filename' => $this->helper->getFileNameFilter(),
        ]);
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, ['path' => $this->helper->getFullPathValidator()]);
        ValidationService::validate($request, ['filename' => $this->helper->getFileNameValidator()]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $query = $request->getQueryParams();
            $file = $this->fileRepository->getByFullPath($query['path']);
        } catch (NoUniqueResultException $e) {
            return new JsonApiErrorResponse(['FILE_NOT_FOUND' => 'File not found'], 404);
        }

        $body = $request->getParsedBody();
        $file->setName(FileNameValue::get($body['filename']));
        $this->fileRepository->updateMetaData($file);
        return $this->renderer->render($request, new JsonApiView($file));
    }
}
