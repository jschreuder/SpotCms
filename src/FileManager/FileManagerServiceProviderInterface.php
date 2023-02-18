<?php declare(strict_types = 1);

namespace Spot\FileManager;

use jschreuder\Middle\View\RendererInterface;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use PDO;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\FileManager\Repository\FileRepository;

interface FileManagerServiceProviderInterface
{
    public function config(string $valueName): mixed;

    public function getDatabase(): PDO;

    public function getObjectRepository(): ObjectRepository;

    public function getAdapter(): FilesystemAdapter;

    public function getFileStorage(): FilesystemOperator;

    public function getFileManagerHelper(): FileManagerHelper;

    public function getFileRepository(): FileRepository;

    public function getFileRenderer(): RendererInterface;

    public function getFileDirectoryRenderer(): RendererInterface;
}
