<?php

namespace spec\Spot\ImageEditor\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Spot\FileManager\FileManagerHelper;
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

    /** @var  FileManagerHelper */
    private $helper;

    /** @var  LoggerInterface */
    private $logger;

    public function let(
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
        $this->beConstructedWith($imageRepository, $imageEditor, $helper, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StoreEditedImageHandler::class);
    }
}
