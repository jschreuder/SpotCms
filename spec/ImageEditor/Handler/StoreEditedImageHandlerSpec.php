<?php

namespace spec\Spot\ImageEditor\Handler;

use Imagine\Image\ImageInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\ImageEditor\Handler\StoreEditedImageHandler;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

/** @mixin  StoreEditedImageHandler */
class StoreEditedImageHandlerSpec extends ObjectBehavior
{
    /** @var  ImageRepository */
    private $imageRepository;

    /** @var  ImageEditor */
    private $imageEditor;

    /** @var  LoggerInterface */
    private $logger;

    public function let(
        ImageRepository $imageRepository,
        ImageEditor $imageEditor,
        LoggerInterface $logger
    )
    {
        $this->imageRepository = $imageRepository;
        $this->imageEditor = $imageEditor;
        $this->logger = $logger;
        $this->beConstructedWith($imageRepository, $imageEditor, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StoreEditedImageHandler::class);
    }

    public function it_can_execute_a_request(
        RequestInterface $request,
        File $file,
        ImageInterface $image,
        File $newImage
    )
    {
        $fullPath = '/path/to/file.ext';
        $operations = ['resize' => ['width' => 320, 'height' => 480]];
        $request->offsetGet('path')->willReturn($fullPath);
        $request->offsetGet('operations')->willReturn($operations);
        $request->getAcceptContentType()->willReturn('*/*');
        $this->imageRepository->getByFullPath($fullPath)->willReturn($file);
        $this->imageEditor->process($file, $operations)->willReturn($image);

        $this->imageEditor->output($file, $image)->willReturn('abcdefg');
        $this->imageRepository->createImage($file, new Argument\Token\TypeToken('resource'))->willReturn($newImage);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->offsetGet('data')->shouldReturn($newImage);
    }

    public function it_will_404_on_image_not_found(RequestInterface $request)
    {
        $fullPath = '/path/to/file.ext';
        $request->offsetGet('path')->willReturn($fullPath);
        $request->getAcceptContentType()->willReturn('*/*');
        $this->imageRepository->getByFullPath($fullPath)->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    public function it_will_error_on_error_during_request_execution(RequestInterface $request)
    {
        $fullPath = '/path/to/file.ext';
        $request->offsetGet('path')->willReturn($fullPath);
        $request->getAcceptContentType()->willReturn('*/*');
        $this->imageRepository->getByFullPath($fullPath)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
