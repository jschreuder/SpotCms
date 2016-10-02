<?php

namespace spec\Spot\ImageEditor\Handler;

use Imagine\Image\ImageInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\MimeTypeValue;
use Spot\ImageEditor\Handler\GetEditedImageController;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

/** @mixin  GetEditedImageController */
class GetEditedImageHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(GetEditedImageController::class);
    }

    public function it_can_execute_a_request(RequestInterface $request, File $file, ImageInterface $image)
    {
        $fullPath = '/path/to/file.ext';
        $operations = ['resize' => ['width' => 320, 'height' => 480]];
        $request->offsetGet('path')->willReturn($fullPath);
        $request->offsetGet('operations')->willReturn($operations);
        $request->getAcceptContentType()->willReturn('*/*');
        $this->imageRepository->getByFullPath($fullPath)->willReturn($file);
        $this->imageEditor->process($file, $operations)->willReturn($image);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn('images.getEdited');
        $response->offsetGet('image')->shouldReturn($image);
        $response->offsetGet('file')->shouldReturn($file);
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

    public function it_can_generate_an_image_response(ResponseInterface $response, File $file, ImageInterface $image)
    {
        $responseMock = 'i-am-not-really-an-image';
        $fileName = FileNameValue::get('image.png');
        $fileMimeType = MimeTypeValue::get('image/png');
        $response->offsetGet('image')->willReturn($image);
        $response->offsetGet('file')->willReturn($file);
        $file->getName()->willReturn($fileName);
        $file->getMimeType()->willReturn($fileMimeType);
        $this->imageEditor->output($file, $image)->willReturn($responseMock);

        $httpResponse = $this->generateResponse($response);
        $httpResponse->shouldHaveType(HttpResponse::class);
        $httpResponse->getStatusCode()->shouldReturn(200);
        $httpResponse->getHeaderLine('Content-Type')->shouldReturn($fileMimeType->toString());
        $httpResponse->getBody()->getContents()->shouldReturn($responseMock);
    }
}
