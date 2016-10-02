<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\View\JsonApiRenderer;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\HtmlContentBlockType;
use Spot\SiteContent\BlockType\RssFeedBlockType;
use Spot\SiteContent\BlockType\VimeoBlockType;
use Spot\SiteContent\BlockType\YoutubeBlockType;
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
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Serializer\PageBlockSerializer;
use Spot\SiteContent\Serializer\PageSerializer;

class SiteContentServiceProvider implements ServiceProviderInterface, RoutingProviderInterface
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
        $container['siteContent.blockTypes'] = function () {
            return new BlockTypeContainer([
                new HtmlContentBlockType(),
                new RssFeedBlockType(),
                new VimeoBlockType(),
                new YoutubeBlockType(),
            ]);
        };
        $container['repository.pages'] = function (Container $container) {
            return new PageRepository($container['db'], $container['repository.objects']);
        };
        $container['renderer.pages'] = function (Container $container) {
            return new JsonApiRenderer($container['http.response_factory'], new PageSerializer());
        };
        $container['renderer.pageBlocks'] = function (Container $container) {
            return new JsonApiRenderer($container['http.response_factory'], new PageBlockSerializer());
        };
    }

    public function registerRoutes(RouterInterface $router)
    {
        $router->post('pages.create', $this->uriSegment, function () {
            return new CreatePageController($this->container['repository.pages'], $this->container['renderer.pages']);
        });
        $router->get('pages.list', $this->uriSegment, function () {
            return new ListPagesController($this->container['repository.pages'], $this->container['renderer.pages']);
        });
        $router->get('pages.get', $this->uriSegment . '/{page_uuid}', function () {
            return new GetPageController($this->container['repository.pages'], $this->container['renderer.pages']);
        }, [], ['page_uuid' => Uuid::VALID_PATTERN]);
        $router->patch('pages.update', $this->uriSegment . '/{page_uuid}', function () {
            return new UpdatePageController($this->container['repository.pages'], $this->container['renderer.pages']);
        }, [], ['page_uuid' => Uuid::VALID_PATTERN]);
        $router->patch('pages.reorder', $this->uriSegment . '/{parent_uuid}/reorder', function () {
            return new ReorderPagesController($this->container['repository.pages'], $this->container['renderer.pages']);
        }, [], ['parent_uuid' => Uuid::VALID_PATTERN]);
        $router->delete('pages.delete', $this->uriSegment . '/{page_uuid}', function () {
            return new DeletePageController($this->container['repository.pages'], $this->container['renderer.pages']);
        }, [], ['page_uuid' => Uuid::VALID_PATTERN]);

        $router->post('pageBlocks.create', $this->uriSegment . '/{page_uuid}/blocks', function () {
            return new AddPageBlockController(
                $this->container['repository.pages'],
                $this->container['siteContent.blockTypes'],
                $this->container['renderer.pageBlocks']
            );
        }, [], ['page_uuid' => Uuid::VALID_PATTERN]);
        $router->get('pageBlocks.get', $this->uriSegment . '/{page_uuid}/blocks/{page_block_uuid}',
            function () {
                return new GetPageBlockController(
                    $this->container['repository.pages'],
                    $this->container['renderer.pageBlocks']
                );
            },
            [],
            ['page_uuid' => Uuid::VALID_PATTERN, 'page_block_uuid' => Uuid::VALID_PATTERN]
        );
        $router->patch('pageBlocks.update', $this->uriSegment . '/{page_uuid}/blocks/{page_block_uuid}',
            function () {
                return new UpdatePageBlockController(
                    $this->container['repository.pages'],
                    $this->container['siteContent.blockTypes'],
                    $this->container['renderer.pageBlocks']
                );
            },
            [],
            ['page_uuid' => Uuid::VALID_PATTERN, 'page_block_uuid' => Uuid::VALID_PATTERN]
        );
        $router->delete('pageBlocks.delete', $this->uriSegment . '/{page_uuid}/blocks/{page_block_uuid}',
            function () {
                return new DeletePageBlockController(
                    $this->container['repository.pages'],
                    $this->container['renderer.pageBlocks']
                );
            },
            [],
            ['page_uuid' => Uuid::VALID_PATTERN, 'page_block_uuid' => Uuid::VALID_PATTERN]
        );
    }
}
