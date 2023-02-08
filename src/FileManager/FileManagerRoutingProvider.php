<?php declare(strict_types = 1);

namespace Spot\FileManager;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Spot\FileManager\Controller\DeleteFileController;
use Spot\FileManager\Controller\GetDirectoryListingController;
use Spot\FileManager\Controller\DownloadFileController;
use Spot\FileManager\Controller\MoveFileController;
use Spot\FileManager\Controller\RenameFileController;
use Spot\FileManager\Controller\UploadFileController;

class FileManagerRoutingProvider implements RoutingProviderInterface
{
    public function __construct(
        private string $uriSegment,
        private FileManagerServiceProviderInterface $container
    )
    {
    }

    public function registerRoutes(RouterInterface $router): void
    {
        $router->post('files.upload', $this->uriSegment . '/f/{path:.+}', function () {
            return new UploadFileController(
                $this->container->getFileRepository(),
                $this->container->getFileManagerHelper(),
                $this->container->getFileRenderer()
            );
        });
        $router->get('files.download', $this->uriSegment . '/f/{path:.+}', function () {
            return new DownloadFileController(
                $this->container->getFileRepository(),
                $this->container->getFileManagerHelper(),
                $this->container->getFileRenderer()
            );
        });
        $router->get('files.getDirectory', $this->uriSegment . '/d/{path:.*}', function () {
            return new GetDirectoryListingController(
                $this->container->getFileRepository(), $this->container->getFileManagerHelper()
            );
        });
        $router->delete('files.delete', $this->uriSegment . '/f/{path:.+}', function () {
            return new DeleteFileController(
                $this->container->getFileRepository(),
                $this->container->getFileManagerHelper(),
                $this->container->getFileRenderer()
            );
        });
        $router->patch('files.rename', $this->uriSegment . '/rename/{path:.+}', function () {
            return new RenameFileController(
                $this->container->getFileRepository(),
                $this->container->getFileManagerHelper(),
                $this->container->getFileRenderer()
            );
        });
        $router->patch('files.move', $this->uriSegment . '/move/{path:.+}', function () {
            return new MoveFileController(
                $this->container->getFileRepository(),
                $this->container->getFileManagerHelper(),
                $this->container->getFileRenderer()
            );
        });
    }
}
