<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use jschreuder\Middle\View\RendererInterface;
use PDO;
use Spot\FileManager\FileManagerHelper;
use Spot\ImageEditor\Repository\ImageRepository;

interface ImageEditorServiceProviderInterface
{
    public function config(string $valueName): mixed;

    public function getFileManagerHelper(): FileManagerHelper;

    public function getDatabase(): PDO;

    public function getImageEditor(): ImageEditor;

    public function getImageRepository(): ImageRepository;

    public function getFileRenderer(): RendererInterface;
}
