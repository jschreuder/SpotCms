<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use Imagine\Gd\Imagine;
use jschreuder\Middle\View\RendererInterface;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Spot\Application\View\JsonApiRenderer;
use Spot\FileManager\Serializer\FileSerializer;
use Spot\ImageEditor\Repository\ImageRepository;

trait ImageEditorServiceProvider
{
    abstract public function getHttpResponseFactory(): ResponseFactoryInterface;
    abstract public function getDatabase(): PDO;

    public function getImageEditor(): ImageEditor
    {
        return new ImageEditor(new Imagine());
    }

    public function getImageRepository(): ImageRepository
    {
        return new ImageRepository($this->getDatabase(), $this->getFileRepository());
    }

    public function getImageRenderer(): RendererInterface
    {
        return new JsonApiRenderer($this->getHttpResponseFactory(), new FileSerializer());
    }
}
