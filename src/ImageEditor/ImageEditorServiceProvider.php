<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use Imagine\Gd\Imagine;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Api\ApplicationServiceProvider;
use Spot\Api\ServiceProvider\RepositoryProviderInterface;
use Spot\Api\ServiceProvider\RoutingProviderInterface;
use Spot\ImageEditor\Handler\GetEditedImageHandler;
use Spot\ImageEditor\Repository\ImageRepository;

class ImageEditorServiceProvider implements
    ServiceProviderInterface,
    RepositoryProviderInterface,
    RoutingProviderInterface
{
    /** @var  string */
    private $uriSegment;

    public function __construct(string $uriSegment)
    {
        $this->uriSegment = $uriSegment;
    }

    public function register(Container $container)
    {
        $container['imageEditor'] = function () {
            return new ImageEditor(new Imagine());
        };
    }

    public function registerRepositories(Container $container)
    {
        $container['repository.images'] = function (Container $container) {
            return new ImageRepository($container['db'], $container['repository.files']);
        };
    }

    public function registerRouting(Container $container, ApplicationServiceProvider $builder)
    {
        // Files API Calls
        $container['handler.images.getEdited'] = function (Container $container) {
            return new GetEditedImageHandler(
                $container['repository.images'],
                $container['imagine'],
                $container['fileManager.helper'],
                $container['logger']
            );
        };
        $container['handler.images.storeEdited'] = function (Container $container) {
            return new StoreEditedImageHandler(
                $container['repository.images'],
                $container['imagine'],
                $container['fileManager.helper'],
                $container['logger']
            );
        };

        // Configure ApiBuilder to use Handlers & Response Generators
        $builder
            ->addParser('GET', $this->uriSegment . '/f/{path:.+}', 'handler.images.getEdited')
            ->addExecutor(GetEditedImageHandler::MESSAGE, 'handler.images.getEdited')
            ->addGenerator(GetEditedImageHandler::MESSAGE, '*/*', 'handler.images.getEdited');
        $builder
            ->addParser('PUT', $this->uriSegment . '/d/{path:.*}', 'handler.storeEdited')
            ->addExecutor(StoreEditedImageHandler::MESSAGE, 'handler.files.storeEdited')
            ->addGenerator(StoreEditedImageHandler::MESSAGE, '*/*', 'responseGenerator.files.single');
    }
}
