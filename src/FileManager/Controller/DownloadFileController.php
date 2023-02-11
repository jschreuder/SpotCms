<?php declare(strict_types = 1);

namespace Spot\FileManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\FilterService;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\ValidationService;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;

class DownloadFileController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    public function __construct(
        private FileRepository $fileRepository,
        private FileManagerHelper $helper
    )
    {
    }

    public function filterRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        return FilterService::filterQuery($request, [
            'path' => $this->helper->getPathFilter(),
        ]);
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, [
            'path' => $this->helper->getFullPathValidator(),
        ]);
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $query = $request->getQueryParams();
            $file = $this->fileRepository->getByFullPath($query['path']);
            return new Response($file->getStream(), 200, [
                'Content-Type' => $file->getMimeType()->toString(),
                'Content-Disposition' => 'attachment; filename="' . $file->getName()->toString() . '"'
            ]);
        } catch (NoUniqueResultException $e) {
            return new JsonApiErrorResponse(['FILE_NOT_FOUND' => 'File not found'], 404);
        }
    }
}
