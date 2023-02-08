<?php declare(strict_types = 1);

namespace Spot\FileManager;

use jschreuder\Middle\View\RendererInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Spot\Application\View\JsonApiRenderer;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Serializer\FileSerializer;

interface FileManagerServiceProviderInterface
{
    public function config(string $valueName): mixed;

    public function getHttpResponseFactory(): ResponseFactoryInterface;

    public function getDatabase(): PDO;

    public function getObjectRepository(): ObjectRepository;

    public function getAdapter(): FilesystemAdapter;

    public function getFileStorage(): FilesystemOperator;

    public function getFileManagerHelper(): FileManagerHelper;

    public function getFileRepository(): FileRepository;

    public function getFileRenderer(): RendererInterface;
}
