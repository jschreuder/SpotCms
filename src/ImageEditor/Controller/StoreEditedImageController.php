<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

class StoreEditedImageController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    use OperationsTrait;

    public function __construct(
        FileManagerHelper $helper,
        private ImageRepository $imageRepository,
        private ImageEditor $imageEditor,
        array $operations,
        private RendererInterface $renderer
    )
    {
        $this->helper = $helper;
        $this->operations = $operations;
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $query = $request->getQueryParams();
            // Fetch existing image and process operations on it
            $file = $this->imageRepository->getByFullPath($query['path']);
            $image = $this->imageEditor->process($file, $query['operations']);

            // Get resulting image and store in stream
            $contents = tmpfile();
            fwrite($contents, $this->imageEditor->output($file, $image));

            $newImage = $this->imageRepository->createImage($file, $contents);
            $this->renderer->render($request, new JsonApiView($newImage));
        } catch (NoUniqueResultException $e) {
            return new JsonApiErrorResponse(['IMAGE_NOT_FOUND' => 'Image not found'], 404);
        }
    }
}
