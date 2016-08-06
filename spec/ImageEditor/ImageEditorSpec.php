<?php

namespace spec\Spot\ImageEditor;

use Imagine\Effects\EffectsInterface;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use PhpSpec\ObjectBehavior;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Value\MimeTypeValue;
use Spot\ImageEditor\ImageEditor;

/** @mixin  ImageEditor */
class ImageEditorSpec extends ObjectBehavior
{
    /** @var  AbstractImagine */
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

    public function it_can_determine_the_jpg_format_from_mime_type(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/jpg'));
        $this->determineImageFormat($file)->shouldReturn('jpeg');
    }

    public function it_can_determine_the_jpe_format_from_mime_type(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/jpe'));
        $this->determineImageFormat($file)->shouldReturn('jpeg');
    }

    public function it_can_determine_the_jpeg_format_from_mime_type(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/jpeg'));
        $this->determineImageFormat($file)->shouldReturn('jpeg');
    }

    public function it_can_determine_the_gif_format_from_mime_type(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/gif'));
        $this->determineImageFormat($file)->shouldReturn('gif');
    }

    public function it_can_determine_the_png_format_from_mime_type(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/png'));
        $this->determineImageFormat($file)->shouldReturn('png');
    }

    public function it_errors_when_it_cant_determine_the_gd_format_from_mime_type(File $file)
    {
        $file->getMimeType()->willReturn(MimeTypeValue::get('application/json'));
        $this->shouldThrow(\RuntimeException::class)->duringDetermineImageFormat($file);
    }

    public function it_can_process_all_the_things(File $file, ImageInterface $image, EffectsInterface $effects)
    {
        $stream = tmpfile();
        $file->getStream()->willReturn($stream);
        $this->imagine->read($stream)->willReturn($image);

        $operations = [
            'resize' => ['width' => 200, 'height' => 300],
            'crop' => ['width' => 180, 'height' => 280, 'x' => 10, 'y' => 10],
            'rotate' => ['degrees' => 180],
            'negative' => ['apply' => true],
            'gamma' => ['correction' => 1.2],
            'greyscale' => ['apply' => true],
            'blur' => ['amount' => 1.1],
        ];

        $image->resize(new Box($operations['resize']['width'], $operations['resize']['height']))->shouldBeCalled();
        $image->crop(
            new Point($operations['crop']['x'], $operations['crop']['y']),
            new Box($operations['crop']['width'], $operations['crop']['height'])
        )->shouldBeCalled();
        $image->rotate($operations['rotate']['degrees'])->shouldBeCalled();
        $image->effects()->willReturn($effects);
        $effects->negative()->shouldBeCalled();
        $effects->gamma($operations['gamma']['correction'])->shouldBeCalled();
        $effects->grayscale()->shouldBeCalled();
        $effects->blur($operations['blur']['amount'])->shouldBeCalled();

        $this->process($file, $operations);
    }

    public function it_will_error_on_invalid_operation(File $file, ImageInterface $image)
    {
        $stream = tmpfile();
        $file->getStream()->willReturn($stream);
        $this->imagine->read($stream)->willReturn($image);

        $this->shouldThrow(\RuntimeException::class)->duringProcess($file, ['nope' => []]);
        $this->shouldThrow(\RuntimeException::class)->duringProcess($file, ['resize' => []]);
    }

    public function it_can_output_the_result(File $file, ImageInterface $image)
    {
        $result = 'resultingimagestring';
        $file->getMimeType()->willReturn(MimeTypeValue::get('image/jpg'));
        $image->get('jpeg')->willReturn($result);

        $this->output($file, $image)->shouldReturn($result);
    }
}
