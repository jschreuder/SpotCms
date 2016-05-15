<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use Imagine\Image\AbstractImagine;
use Spot\FileManager\Entity\File;

class ImageEditor
{
    /** @var  AbstractImagine */
    private $imagine;

    public function __construct(AbstractImagine $imagine)
    {
        $this->imagine = $imagine;
    }

    public function isImage(File $file) : bool
    {
        return preg_match('#^image/(jpeg|jpg|jpe|gif|png)$#ui', $file->getMimeType()->toString()) !== 0;
    }

    /** @return  resource */
    public function process(File $file)
    {
        // @todo
    }
}
