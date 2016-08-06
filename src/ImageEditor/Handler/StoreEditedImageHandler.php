<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

class StoreEditedImageHandler implements ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'images.storeEdited';

    /** @var  ImageRepository */
    private $imageRepository;

    /** @var  ImageEditor */
    private $imageEditor;

    public function __construct(
        ImageRepository $imageRepository,
        ImageEditor $imageEditor,
        LoggerInterface $logger
    )
    {
        $this->imageRepository = $imageRepository;
        $this->imageEditor = $imageEditor;
        $this->logger = $logger;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $file = $this->imageRepository->getByFullPath($request['path']);
            $image = $this->imageEditor->process($file, $request['operations']);

            $contents = tmpfile();
            fwrite($contents, $this->imageEditor->output($file, $image));
            unset($image);
            rewind($contents);

            $newImage = $this->imageRepository->createImage($file, $contents);
            return new Response(self::MESSAGE, ['data' => $newImage], $request);
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
}
