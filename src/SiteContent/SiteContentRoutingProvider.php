<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Validator\GenericValidator;
use Spot\SiteContent\Controller\AddPageBlockController;
use Spot\SiteContent\Controller\CreatePageController;
use Spot\SiteContent\Controller\DeletePageController;
use Spot\SiteContent\Controller\DeletePageBlockController;
use Spot\SiteContent\Controller\GetPageController;
use Spot\SiteContent\Controller\GetPageBlockController;
use Spot\SiteContent\Controller\ListPagesController;
use Spot\SiteContent\Controller\ReorderPagesController;
use Spot\SiteContent\Controller\UpdatePageController;
use Spot\SiteContent\Controller\UpdatePageBlockController;

class SiteContentRoutingProvider implements RoutingProviderInterface
{
    public function __construct(
        private string $uriSegment, 
        private SiteContentServiceProviderInterface $container
    )
    {
    }

    public function registerRoutes(RouterInterface $router): void
    {
        $uuidPattern = (new GenericValidator())->getPattern();

        $router->post('pages.create', $this->uriSegment, function () {
            return new CreatePageController($this->container->getPageRepository(), $this->container->getPageRenderer());
        });
        $router->get('pages.list', $this->uriSegment, function () {
            return new ListPagesController($this->container->getPageRepository(), $this->container->getPageRenderer());
        });
        $router->get('pages.get', $this->uriSegment . '/{page_uuid}', function () {
            return new GetPageController($this->container->getPageRepository(), $this->container->getPageRenderer());
        }, [], ['page_uuid' => $uuidPattern]);
        $router->patch('pages.update', $this->uriSegment . '/{page_uuid}', function () {
            return new UpdatePageController($this->container->getPageRepository(), $this->container->getPageRenderer());
        }, [], ['page_uuid' => $uuidPattern]);
        $router->patch('pages.reorder', $this->uriSegment . '/{parent_uuid}/reorder', function () {
            return new ReorderPagesController($this->container->getPageRepository(), $this->container->getPageRenderer());
        }, [], ['parent_uuid' => $uuidPattern]);
        $router->delete('pages.delete', $this->uriSegment . '/{page_uuid}', function () {
            return new DeletePageController($this->container->getPageRepository(), $this->container->getPageRenderer());
        }, [], ['page_uuid' => $uuidPattern]);

        $router->post('pageBlocks.create', $this->uriSegment . '/{page_uuid}/blocks', function () {
            return new AddPageBlockController(
                $this->container->getPageRepository(),
                $this->container->config('siteContent.blockTypes'),
                $this->container->getPageBlockRenderer()
            );
        }, [], ['page_uuid' => $uuidPattern]);
        $router->get('pageBlocks.get', $this->uriSegment . '/{page_uuid}/blocks/{page_block_uuid}',
            function () {
                return new GetPageBlockController(
                    $this->container->getPageRepository(),
                    $this->container->getPageBlockRenderer()
                );
            },
            [],
            ['page_uuid' => $uuidPattern, 'page_block_uuid' => $uuidPattern]
        );
        $router->patch('pageBlocks.update', $this->uriSegment . '/{page_uuid}/blocks/{page_block_uuid}',
            function () {
                return new UpdatePageBlockController(
                    $this->container->getPageRepository(),
                    $this->container->config('siteContent.blockTypes'),
                    $this->container->getPageBlockRenderer()
                );
            },
            [],
            ['page_uuid' => $uuidPattern, 'page_block_uuid' => $uuidPattern]
        );
        $router->delete('pageBlocks.delete', $this->uriSegment . '/{page_uuid}/blocks/{page_block_uuid}',
            function () {
                return new DeletePageBlockController(
                    $this->container->getPageRepository(),
                    $this->container->getPageBlockRenderer()
                );
            },
            [],
            ['page_uuid' => $uuidPattern, 'page_block_uuid' => $uuidPattern]
        );
    }
}
