<?php

namespace spec\Spot\ImageEditor\Controller;

use Imagine\Image\ImageInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\MimeTypeValue;
use Spot\ImageEditor\Controller\GetEditedImageController;
use Spot\ImageEditor\Controller\Operation\OperationInterface;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

/** @mixin  GetEditedImageController */
class GetEditedImageControllerSpec extends ObjectBehavior
{
    /** @var  FileManagerHelper */
    private $helper;

    /** @var  ImageRepository */
    private $imageRepository;

    /** @var  ImageEditor */
    private $imageEditor;

    /** @var  OperationInterface */
    private $operation1;

    /** @var  OperationInterface */
    private $operation2;

    public function let(
        ImageRepository $imageRepository,
        ImageEditor $imageEditor,
        OperationInterface $operation1,
        OperationInterface $operation2
    )
    {
        $this->helper = new FileManagerHelper();
        $this->imageRepository = $imageRepository;
        $this->imageEditor = $imageEditor;
        $this->operation1 = $operation1;
        $this->operation2 = $operation2;
        $this->beConstructedWith($this->helper, $imageRepository, $imageEditor, [$operation1, $operation2]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GetEditedImageController::class);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, File $file, FileNameValue $fileName, MimeTypeValue $mimeType, ImageInterface $image)
    {
        $query = [
            'path' => '/path/to/file.ext',
            'operations' => [
                'resize' => ['width' => 320, 'height' => 480],
            ],
        ];
        $request->getQueryParams()->willReturn($query);
        $this->imageRepository->getByFullPath($query['path'])->willReturn($file);
        $this->imageEditor->process($file, $query['operations'])->willReturn($image);

        $imageStream = 'i-am-not-an-image';
        $this->imageEditor->output($file, $image)->willReturn($imageStream);

        $file->getName()->willReturn($fileName);
        $fileName->toString()->willReturn('file.ext');
        $file->getMimeType()->willReturn($mimeType);
        $mimeType->toString()->willReturn('fake/ext');

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
    }

    public function it_will_404_on_image_not_found(ServerRequestInterface $request)
    {
        $query = [
            'path' => '/path/to/file.ext',
        ];
        $request->getQueryParams()->willReturn($query);
        $this->imageRepository->getByFullPath($query['path'])->willThrow(new NoUniqueResultException());

        $response = $this->execute($request);
        $response->shouldHaveType(JsonApiErrorResponse::class);
    }
}
