<?php declare(strict_types = 1);

namespace Spot\FileManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spot\Application\FilterService;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonApiView;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;

class UploadFileController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
        ValidationService::validateQuery($request, ['path' => $this->helper->getPathValidator()]);
        ValidationService::requireUploads($request);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        /** @var  UploadedFileInterface[] $uploadedFiles */
        $uploadedFiles = $request->getUploadedFiles();
        $query = $request->getQueryParams();
        $file = $this->createFiles($uploadedFiles, $query['path']);

        return $this->renderer->render($request, new JsonApiView($file));
    }

    /**
     * @param   UploadedFileInterface[] $uploadedFiles
     * @param   string $path
     * @return  File[]
     */
    private function createFiles(array $uploadedFiles, string $path) : array
    {
        $files = [];
        foreach ($uploadedFiles as $uploadedFile) {
            $file = $this->fileRepository->fromInput(
                substr($uploadedFile->getClientFilename(), 0, 92),
                $path,
                $uploadedFile->getClientMediaType(),
                $uploadedFile->getStream()
            );
            $files[] = $file;
            $this->fileRepository->createFromUpload($file);
        }
        return $files;
    }
}
