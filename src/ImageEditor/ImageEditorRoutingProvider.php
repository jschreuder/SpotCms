<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Spot\ImageEditor\Controller\GetEditedImageController;
use Spot\ImageEditor\Controller\StoreEditedImageController;

class ImageEditorRoutingProvider implements RoutingProviderInterface
{
    public function __construct(
        private string $uriSegment,
        private ImageEditorServiceProviderInterface $container
    )
    {
    }

    public function registerRoutes(RouterInterface $router): void
    {
        $router->get('images.getProcessed', $this->uriSegment . '/f/{path:.+}', function () {
            return new GetEditedImageController(
                $this->container->getFileManagerHelper(),
                $this->container->getImageRepository(),
                $this->container->getImageEditor(),
                $this->container->config('imageEditor.operations')
            );
        });
        $router->patch('images.applyProcessing', $this->uriSegment . '/f/{path:.*}', function () {
            return new StoreEditedImageController(
                $this->container->getFileManagerHelper(),
                $this->container->getImageRepository(),
                $this->container->getImageEditor(),
                $this->container->config('imageEditor.operations'),
                $this->container->getImageRenderer()
            );
        });
    }
}
