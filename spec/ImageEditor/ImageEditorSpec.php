<?php

namespace spec\Spot\ImageEditor;

use Imagine\Image\AbstractImagine;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Value\MimeTypeValue;
use Spot\ImageEditor\ImageEditor;

/** @mixin  ImageEditor */
class ImageEditorSpec extends ObjectBehavior
{
    private $imagine;

    public function let(AbstractImagine $imagine)
    {
        $this->imagine = $imagine;
        $this->beConstructedWith($imagine);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ImageEditor::class);
    }

    public function it_can_recognize_a_non_image(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('application/json'));
        $this->isImage($file)->shouldReturn(false);
    }

    public function it_can_recognize_an_image(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/jpg'));
        $this->isImage($file)->shouldReturn(true);
    }
}
