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
use Spot\FileManager\Value\FilePathValue;

class MoveFileController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
        $this->helper->addPathFilter($filter, 'new_path');
        $data = $filter->filter([
            'path' => $request->getAttribute('path'),
            'new_path' => $request->getParsedBody()['new_path'],
        ]);

        return $request
            ->withParsedBody(['new_path' => $data['new_path']])
            ->withAttribute('path', $data['path']);
    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $this->helper->addFullPathValidator($validator, 'path');
        $this->helper->addPathValidator($validator, 'new_path');

        $result = $validator->validate([
            'path' => $request->getAttribute('path'),
            'new_path' => $request->getParsedBody()['new_path'],
        ]);
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

        $file->setPath(FilePathValue::get($request['new_path']));
        $this->fileRepository->updateMetaData($file);
        return $this->renderer->render($request, new JsonApiView($file));
    }
}
