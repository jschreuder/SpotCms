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
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;

class DeleteFileController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
        $this->helper->addFullPathValidator($validator, 'path');

        $result = $validator->validate($request->getAttributes());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $file = $this->fileRepository->getByFullPath($request['path']);
        } catch (NoUniqueResultException $e) {
            return new JsonApiErrorResponse(['FILE_NOT_FOUND' => 'File not found'], 404);
        }

        $this->fileRepository->delete($file);
        return $this->renderer->render($request, new JsonApiView($file));
    }
}
