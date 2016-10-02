<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller;

use Imagine\Image\ImageInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;
use Zend\Diactoros\Response;

class GetEditedImageController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    use OperationsTrait;

    /** @var  ImageRepository */
    private $imageRepository;

    /** @var  ImageEditor */
    private $imageEditor;

    public function __construct(
        FileManagerHelper $helper,
        ImageRepository $imageRepository,
        ImageEditor $imageEditor,
        array $operations
    )
    {
        $this->helper = $helper;
        $this->imageRepository = $imageRepository;
        $this->imageEditor = $imageEditor;
        $this->operations = $operations;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $file = $this->imageRepository->getByFullPath($request['path']);
            $image = $this->imageEditor->process($file, $request['operations']);
            return $this->generateResponse($file, $image);
        } catch (NoUniqueResultException $e) {
            return new JsonApiErrorResponse(['IMAGE_NOT_FOUND' => 'Image not found'], 404);
        }
    }

    private function generateResponse(File $file, ImageInterface $image) : ResponseInterface
    {
        $imageStream = tmpfile();
        fputs($imageStream, $this->imageEditor->output($file, $image));
        rewind($imageStream);

        return new Response(
            $imageStream,
            200,
            [
                'Content-Type' => $file->getMimeType()->toString(),
                'Content-Disposition' => 'attachment; filename="' . $file->getName()->toString() . '"'
            ]
        );
    }
}
