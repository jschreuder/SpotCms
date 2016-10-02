<?php declare(strict_types = 1);

namespace Spot\FileManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spot\Application\View\JsonApiView;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;

class UploadFileController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    /** @var  FileRepository */
    private $fileRepository;

    /** @var  FileManagerHelper */
    private $helper;

    /** @var  RendererInterface */
    private $renderer;

    public function __construct(FileRepository $fileRepository, FileManagerHelper $helper, RendererInterface $renderer)
    {
        $this->fileRepository = $fileRepository;
        $this->helper = $helper;
        $this->renderer = $renderer;
    }

    public function filterRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        $filter = new Filter();
        $this->helper->addPathFilter($filter, 'path');
        $attributes = $filter->filter($request->getAttributes());
        return $request->withAttribute('path', $attributes['path']);
    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $this->helper->addPathValidator($validator, 'path');
        $validator->required('files')->callback(function ($array) { return is_array($array) && count($array) > 0; });

        $result = $validator->validate([
            'path' => $request->getAttribute('path'),
            'files' => $request->getUploadedFiles(),
        ]);
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var  UploadedFileInterface[] $uploadedFiles */
        $uploadedFiles = $request['files'];
        $path = $request['path'];
        $file = $this->createFiles($uploadedFiles, $path);

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
            $files[] = $file = $this->fileRepository->fromInput(
                substr($uploadedFile->getClientFilename(), 0, 92),
                $path,
                $uploadedFile->getClientMediaType(),
                $uploadedFile->getStream()
            );
            $this->fileRepository->createFromUpload($file);
        }
        return $files;
    }
}
