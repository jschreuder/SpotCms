<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\Response\Generator\MultiEntityGenerator;
use Spot\Api\Response\Generator\SingleEntityGenerator;
use Spot\Api\Response\ResponseInterface;
use Spot\Api\ApplicationServiceProvider;
use Spot\Api\ServiceProvider\RepositoryProviderInterface;
use Spot\Api\ServiceProvider\RoutingProviderInterface;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\HtmlContentBlockType;
use Spot\SiteContent\BlockType\RssFeedBlockType;
use Spot\SiteContent\BlockType\VimeoBlockType;
use Spot\SiteContent\BlockType\YoutubeBlockType;
use Spot\SiteContent\Handler\AddPageBlockHandler;
use Spot\SiteContent\Handler\CreatePageHandler;
use Spot\SiteContent\Handler\DeletePageHandler;
use Spot\SiteContent\Handler\DeletePageBlockHandler;
use Spot\SiteContent\Handler\GetPageHandler;
use Spot\SiteContent\Handler\GetPageBlockHandler;
use Spot\SiteContent\Handler\ListPagesHandler;
use Spot\SiteContent\Handler\ReorderPagesHandler;
use Spot\SiteContent\Handler\UpdatePageHandler;
use Spot\SiteContent\Handler\UpdatePageBlockHandler;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Serializer\PageBlockSerializer;
use Spot\SiteContent\Serializer\PageSerializer;

class SiteContentServiceProvider implements
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
        $container['siteContent.blockTypes'] = function () {
            return new BlockTypeContainer([
                new HtmlContentBlockType(),
                new RssFeedBlockType(),
                new VimeoBlockType(),
                new YoutubeBlockType(),
            ]);
        };
    }

    public function registerRepositories(Container $container)
    {
        $container['repository.pages'] = function (Container $container) {
            return new PageRepository($container['db'], $container['repository.objects']);
        };
    }

    public function registerRouting(Container $container, ApplicationServiceProvider $builder)
    {
        // Pages API Calls
        $container['handler.pages.create'] = function (Container $container) {
            return new CreatePageHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pages.list'] = function (Container $container) {
            return new ListPagesHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pages.get'] = function (Container $container) {
            return new GetPageHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pages.update'] = function (Container $container) {
            return new UpdatePageHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pages.reorder'] = function (Container $container) {
            return new ReorderPagesHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pages.delete'] = function (Container $container) {
            return new DeletePageHandler($container['repository.pages'], $container['logger']);
        };

        // PageBlocks API Calls
        $container['handler.pageBlocks.create'] = function (Container $container) {
            return new AddPageBlockHandler(
                $container['repository.pages'],
                $container['siteContent.blockTypes'],
                $container['logger']
            );
        };
        $container['handler.pageBlocks.get'] = function (Container $container) {
            return new GetPageBlockHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pageBlocks.update'] = function (Container $container) {
            return new UpdatePageBlockHandler(
                $container['repository.pages'],
                $container['siteContent.blockTypes'],
                $container['logger']
            );
        };
        $container['handler.pageBlocks.delete'] = function (Container $container) {
            return new DeletePageBlockHandler($container['repository.pages'], $container['logger']);
        };

        // Response Generators for both
        $container['responseGenerator.pages.single'] = function (Container $container) {
            return new SingleEntityGenerator(new PageSerializer(), null, $container['logger']);
        };
        $container['responseGenerator.pages.multi'] = function (Container $container) {
            return new MultiEntityGenerator(
                new PageSerializer(),
                function (ResponseInterface $response) : array {
                    $metaData = [
                        'parent_uuid' => (isset($response['parent_uuid'])
                            && $response['parent_uuid'] instanceof UuidInterface)
                            ? $response['parent_uuid']->toString() : null,
                    ];
                    return $metaData;
                },
                $container['logger']
            );
        };
        $container['responseGenerator.pageBlocks.single'] = function (Container $container) {
            return new SingleEntityGenerator(new PageBlockSerializer(), null, $container['logger']);
        };

        // Configure ApiBuilder to use Handlers & Response Generators
        $builder
            ->addParser('POST', $this->uriSegment, 'handler.pages.create')
            ->addExecutor(CreatePageHandler::MESSAGE, 'handler.pages.create')
            ->addGenerator(CreatePageHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pages.single');
        $builder
            ->addParser('GET', $this->uriSegment, 'handler.pages.list')
            ->addExecutor(ListPagesHandler::MESSAGE, 'handler.pages.list')
            ->addGenerator(ListPagesHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pages.multi');
        $builder
            ->addParser('GET', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'handler.pages.get')
            ->addExecutor(GetPageHandler::MESSAGE, 'handler.pages.get')
            ->addGenerator(GetPageHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pages.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'handler.pages.update')
            ->addExecutor(UpdatePageHandler::MESSAGE, 'handler.pages.update')
            ->addGenerator(UpdatePageHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pages.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{parent_uuid:[0-9a-z\-]+}/reorder', 'handler.pages.reorder')
            ->addExecutor(ReorderPagesHandler::MESSAGE, 'handler.pages.reorder')
            ->addGenerator(ReorderPagesHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pages.multi');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'handler.pages.delete')
            ->addExecutor(DeletePageHandler::MESSAGE, 'handler.pages.delete')
            ->addGenerator(DeletePageHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pages.single');
        $builder
            ->addParser('POST', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks', 'handler.pageBlocks.create')
            ->addExecutor(AddPageBlockHandler::MESSAGE, 'handler.pageBlocks.create')
            ->addGenerator(AddPageBlockHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('GET', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}',
                'handler.pageBlocks.get')
            ->addExecutor(GetPageBlockHandler::MESSAGE, 'handler.pageBlocks.get')
            ->addGenerator(GetPageBlockHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}',
                'handler.pageBlocks.update')
            ->addExecutor(UpdatePageBlockHandler::MESSAGE, 'handler.pageBlocks.update')
            ->addGenerator(UpdatePageBlockHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}',
                'handler.pageBlocks.delete')
            ->addExecutor(DeletePageBlockHandler::MESSAGE, 'handler.pageBlocks.delete')
            ->addGenerator(DeletePageBlockHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.pageBlocks.single');
    }
}
