<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

class StoreEditedImageHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    use LoggableTrait;

    const MESSAGE = 'images.storeEdited';

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

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);
        $this->helper->addPathFilter($rpHelper->getFilter(), 'path');
        $this->helper->addFullPathValidator($rpHelper->getValidator(), 'path');

        return new Request(self::MESSAGE, $rpHelper->filterAndValidate($attributes), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $file = $this->imageRepository->getByFullPath($request['path']);
            $image = $this->imageEditor->process($file);
            return new Response(self::MESSAGE, ['data' => $image, 'file' => $file], $request);
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
        return new \Zend\Diactoros\Response($image, 200, [
            'Content-Type' => $file->getMimeType()->toString(),
            'Content-Disposition' => 'attachment; filename="' . $file->getName()->toString() . '"'
        ]);
    }
}
