<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Handler;

use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

class GetEditedImageHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    use LoggableTrait, OperationsHttpRequestParserTrait;

    const MESSAGE = 'images.getEdited';

    /** @var  ImageRepository */
    private $imageRepository;

    /** @var  ImageEditor */
    private $imageEditor;

    /** @var  FileManagerHelper */
    private $helper;

    public function __construct(
        ImageRepository $imageRepository,
        ImageEditor $imageEditor,
        FileManagerHelper $helper,
        LoggerInterface $logger
    )
    {
        $this->imageRepository = $imageRepository;
        $this->imageEditor = $imageEditor;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $file = $this->imageRepository->getByFullPath($request['path']);
            $image = $this->imageEditor->process($file, $request['operations']);
            return new Response(self::MESSAGE, ['image' => $image, 'file' => $file], $request);
        } catch (NoUniqueResultException $e) {
            return new NotFoundResponse([], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during GetEditedImageHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    public function generateResponse(ResponseInterface $response) : HttpResponse
    {
        /** @var  File $file */
        $file = $response['file'];
        $image = $response['image'];

        $imageStream = tmpfile();
        fputs($imageStream, $this->imageEditor->output($file, $image));
        rewind($imageStream);

        return new \Zend\Diactoros\Response(
            $imageStream,
            200,
            [
                'Content-Type' => $file->getMimeType()->toString(),
                'Content-Disposition' => 'attachment; filename="' . $file->getName()->toString() . '"'
            ]
        );
    }
}
