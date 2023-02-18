<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use Imagine\Gd\Imagine;
use jschreuder\Middle\View\RendererInterface;
use PDO;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\Repository\ImageRepository;

trait ImageEditorServiceProvider
{
    abstract public function getDatabase(): PDO;
    abstract public function getFileManagerHelper(): FileManagerHelper;
    abstract public function getFileRenderer(): RendererInterface;

    public function getImageEditor(): ImageEditor
    {
        return new ImageEditor(new Imagine());
    }

    public function getImageRepository(): ImageRepository
    {
        return new ImageRepository($this->getDatabase(), $this->getFileRepository());
    }
}
