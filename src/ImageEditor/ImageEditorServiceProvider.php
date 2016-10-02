<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use Imagine\Gd\Imagine;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Application\View\JsonApiRenderer;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Serializer\FileSerializer;
use Spot\ImageEditor\Controller\GetEditedImageController;
use Spot\ImageEditor\Controller\Operation\BlurOperation;
use Spot\ImageEditor\Controller\Operation\CropOperation;
use Spot\ImageEditor\Controller\Operation\GammaOperation;
use Spot\ImageEditor\Controller\Operation\GreyscaleOperation;
use Spot\ImageEditor\Controller\Operation\NegativeOperation;
use Spot\ImageEditor\Controller\Operation\ResizeOperation;
use Spot\ImageEditor\Controller\Operation\RotateOperation;
use Spot\ImageEditor\Controller\StoreEditedImageController;
use Spot\ImageEditor\Repository\ImageRepository;

class ImageEditorServiceProvider implements ServiceProviderInterface, RoutingProviderInterface
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
        $container['imageEditor'] = function () {
            return new ImageEditor(new Imagine());
        };
        $container['fileManager.helper'] = function () {
            return new FileManagerHelper();
        };
        $container['repository.images'] = function (Container $container) {
            return new ImageRepository($container['db'], $container['repository.files']);
        };
        $container['images.operations'] = function () {
            return [
                new BlurOperation(),
                new CropOperation(),
                new GammaOperation(),
                new GreyscaleOperation(),
                new NegativeOperation(),
                new ResizeOperation(),
                new RotateOperation(),
            ];
        };
        $container['renderer.images'] = function (Container $container) {
            return new JsonApiRenderer($container['http.response_factory'], new FileSerializer());
        };
    }

    public function registerRoutes(RouterInterface $router)
    {
        $router->get('images.getProcessed', $this->uriSegment . '/f/{path:.+}', function () {
            return new GetEditedImageController(
                $this->container['fileManager.helper'],
                $this->container['repository.images'],
                $this->container['imagine'],
                $this->container['images.operations']
            );
        });
        $router->patch('images.applyProcessing', $this->uriSegment . '/f/{path:.*}', function () {
            return new StoreEditedImageController(
                $this->container['fileManager.helper'],
                $this->container['repository.images'],
                $this->container['imagine'],
                $this->container['images.operations'],
                $this->container['renderer.images']
            );
        });
    }
}
