<?php declare(strict_types = 1);

namespace Spot\FileManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;

class GetDirectoryListingController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    /** @var  FileRepository */
    private $fileRepository;

    /** @var  FileManagerHelper */
    private $helper;

    public function __construct(FileRepository $fileRepository, FileManagerHelper $helper)
    {
        $this->fileRepository = $fileRepository;
        $this->helper = $helper;
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
        $this->helper->addFullPathValidator($validator, 'path');

        $result = $validator->validate($request->getAttributes());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $path = $request['path'];
        $directories = $this->fileRepository->getDirectoriesInPath($path);
        $fileNames = $this->fileRepository->getFileNamesInPath($path);
        return new Response(self::MESSAGE, [
            'data' => [
                'path' => $path,
                'directories' => $directories,
                'files' => $fileNames
            ],
        ], $request);
    }
}
