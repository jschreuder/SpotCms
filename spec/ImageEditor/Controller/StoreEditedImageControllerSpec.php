<?php

namespace spec\Spot\ImageEditor\Controller;

use jschreuder\Middle\View\RendererInterface;
use Imagine\Image\ImageInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\Controller\Operation\OperationInterface;
use Spot\ImageEditor\Controller\StoreEditedImageController;
use Spot\ImageEditor\ImageEditor;
use Spot\ImageEditor\Repository\ImageRepository;

/** @mixin  StoreEditedImageController */
class StoreEditedImageControllerSpec extends ObjectBehavior
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

    /** @var  RendererInterface */
    private $renderer;

    public function let(
        ImageRepository $imageRepository,
        ImageEditor $imageEditor,
        OperationInterface $operation1,
        OperationInterface $operation2,
        RendererInterface $renderer
    )
    {
        $this->helper = new FileManagerHelper();
        $this->imageRepository = $imageRepository;
        $this->imageEditor = $imageEditor;
        $this->renderer = $renderer;
        $this->beConstructedWith($this->helper, $imageRepository, $imageEditor, [$operation1, $operation2], $renderer);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StoreEditedImageController::class);
    }

    public function it_can_execute_a_request(
        ServerRequestInterface $request,
        File $file,
        ImageInterface $image,
        File $newImage,
        ResponseInterface $response
    )
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

        $this->imageEditor->output($file, $image)->willReturn('abcdefg');
        $this->imageRepository->createImage($file, new Argument\Token\TypeToken('resource'))->willReturn($newImage);

        $this->renderer->render($request, Argument::type(JsonApiView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
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
