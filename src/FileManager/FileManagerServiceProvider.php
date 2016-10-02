<?php declare(strict_types = 1);

namespace Spot\FileManager;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use League\Flysystem\Filesystem;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Application\View\JsonApiRenderer;
use Spot\FileManager\Controller\DeleteFileController;
use Spot\FileManager\Controller\GetDirectoryListingController;
use Spot\FileManager\Controller\DownloadFileController;
use Spot\FileManager\Controller\MoveFileController;
use Spot\FileManager\Controller\RenameFileController;
use Spot\FileManager\Controller\UploadFileController;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Serializer\FileSerializer;

class FileManagerServiceProvider implements ServiceProviderInterface, RoutingProviderInterface
{
    /** @var  Container */
    private $container;

    /** @var  string */
    private $uriSegment;

    public function __construct(Container $container, string $uriSegment)
    {
        $this->container = $container;
        $container->register($this);

        $this->uriSegment = $uriSegment;
    }

    public function register(Container $container)
    {
        $container['fileStorage'] = function (Container $container) {
            return new Filesystem($container['fileManager.adapter']);
        };
        $container['fileManager.helper'] = function () {
            return new FileManagerHelper();
        };
        $container['repository.files'] = function (Container $container) {
            return new FileRepository($container['fileStorage'], $container['db'], $container['repository.objects']);
        };
        $container['renderer.files'] = function (Container $container) {
            return new JsonApiRenderer($container['http.response_factory'], new FileSerializer());
        };
    }

    public function registerRoutes(RouterInterface $router)
    {
        $router->post('files.upload', $this->uriSegment . '/f/{path:.+}', function () {
            return new UploadFileController(
                $this->container['repository.files'],
                $this->container['fileManager.helper'],
                $this->container['renderer.files']
            );
        });
        $router->get('files.download', $this->uriSegment . '/f/{path:.+}', function () {
            return new DownloadFileController(
                $this->container['repository.files'],
                $this->container['fileManager.helper'],
                $this->container['renderer.files']
            );
        });
        $router->get('files.getDirectory', $this->uriSegment . '/d/{path:.*}', function () {
            return new GetDirectoryListingController(
                $this->container['repository.files'], $this->container['fileManager.helper']
            );
        });
        $router->delete('files.delete', $this->uriSegment . '/f/{path:.+}', function () {
            return new DeleteFileController(
                $this->container['repository.files'],
                $this->container['fileManager.helper'],
                $this->container['renderer.files']
            );
        });
        $router->patch('files.rename', $this->uriSegment . '/rename/{path:.+}', function () {
            return new RenameFileController(
                $this->container['repository.files'],
                $this->container['fileManager.helper'],
                $this->container['renderer.files']
            );
        });
        $router->patch('files.move', $this->uriSegment . '/move/{path:.+}', function () {
            return new MoveFileController(
                $this->container['repository.files'],
                $this->container['fileManager.helper'],
                $this->container['renderer.files']
            );
        });
    }
}
