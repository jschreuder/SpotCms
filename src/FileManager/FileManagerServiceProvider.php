<?php declare(strict_types = 1);

namespace Spot\FileManager;

use jschreuder\Middle\View\RendererInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PDO;
use Spot\Application\View\JsonConverterRenderer;
use Spot\Application\View\JsonRenderer;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Schema\FileJsonConverter;

trait FileManagerServiceProvider
{
    abstract public function config(string $valueName): mixed;
    abstract public function getDatabase(): PDO;
    abstract public function getObjectRepository(): ObjectRepository;

    public function getAdapter(): FilesystemAdapter
    {
        switch ($this->config('fileManager.adapter')) {
            case 'local':
                return new LocalFilesystemAdapter($this->config('fileManager.localPath'));
        }

        throw new \OutOfBoundsException('No FileManager adapter configured');
    }

    public function getFileStorage(): FilesystemOperator
    {
        return new Filesystem($this->getAdapter());
    }

    public function getFileManagerHelper(): FileManagerHelper
    {
        return new FileManagerHelper();
    }

    public function getFileRepository(): FileRepository
    {
        return new FileRepository($this->getFileStorage(), $this->getDatabase(), $this->getObjectRepository());
    }

    public function getFileRenderer(): RendererInterface
    {
        return new JsonConverterRenderer(new FileJsonConverter());
    }

    public function getFileDirectoryRenderer(): RendererInterface
    {
        return new JsonRenderer();
    }
}
