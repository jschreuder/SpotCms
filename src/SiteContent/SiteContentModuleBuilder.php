<?php declare(strict_types=1);

namespace Spot\SiteContent;

use Pimple\Container;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\Response\Generator\MultiEntityGenerator;
use Spot\Api\Response\Generator\SingleEntityGenerator;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Common\ApiBuilder\ApiBuilder;
use Spot\Common\ApiBuilder\RepositoryBuilderInterface;
use Spot\Common\ApiBuilder\RouterBuilderInterface;
use Spot\SiteContent\Handler\AddPageBlockHandler;
use Spot\SiteContent\Handler\CreatePageHandler;
use Spot\SiteContent\Handler\DeletePageHandler;
use Spot\SiteContent\Handler\DeletePageBlockHandler;
use Spot\SiteContent\Handler\GetPageHandler;
use Spot\SiteContent\Handler\GetPageBlockHandler;
use Spot\SiteContent\Handler\ListPagesHandler;
use Spot\SiteContent\Handler\UpdatePageHandler;
use Spot\SiteContent\Handler\UpdatePageBlockHandler;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Serializer\PageBlockSerializer;
use Spot\SiteContent\Serializer\PageSerializer;

class SiteContentModuleBuilder implements RouterBuilderInterface, RepositoryBuilderInterface
{
    /** @var  string */
    private $uriSegment;

    public function __construct(string $uriSegment)
    {
        $this->uriSegment = $uriSegment;
    }

    public function configureRouting(Container $container, ApiBuilder $builder)
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
        $container['handler.pages.delete'] = function (Container $container) {
            return new DeletePageHandler($container['repository.pages'], $container['logger']);
        };

        // PageBlocks API Calls
        $container['handler.pageBlocks.create'] = function (Container $container) {
            return new AddPageBlockHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pageBlocks.get'] = function (Container $container) {
            return new GetPageBlockHandler($container['repository.pages'], $container['logger']);
        };
        $container['handler.pageBlocks.update'] = function (Container $container) {
            return new UpdatePageBlockHandler($container['repository.pages'], $container['logger']);
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
            ->addRequestExecutor(CreatePageHandler::MESSAGE, 'handler.pages.create')
            ->addResponseGenerator(CreatePageHandler::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('GET', $this->uriSegment, 'handler.pages.list')
            ->addRequestExecutor(ListPagesHandler::MESSAGE, 'handler.pages.list')
            ->addResponseGenerator(ListPagesHandler::MESSAGE, 'responseGenerator.pages.multi');
        $builder
            ->addParser('GET', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'handler.pages.get')
            ->addRequestExecutor(GetPageHandler::MESSAGE, 'handler.pages.get')
            ->addResponseGenerator(GetPageHandler::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'handler.pages.update')
            ->addRequestExecutor(UpdatePageHandler::MESSAGE, 'handler.pages.update')
            ->addResponseGenerator(UpdatePageHandler::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'handler.pages.delete')
            ->addRequestExecutor(DeletePageHandler::MESSAGE, 'handler.pages.delete')
            ->addResponseGenerator(DeletePageHandler::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('POST', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks', 'handler.pageBlocks.create')
            ->addRequestExecutor(AddPageBlockHandler::MESSAGE, 'handler.pageBlocks.create')
            ->addResponseGenerator(AddPageBlockHandler::MESSAGE, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('GET', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}', 'handler.pageBlocks.get')
            ->addRequestExecutor(GetPageBlockHandler::MESSAGE, 'handler.pageBlocks.get')
            ->addResponseGenerator(GetPageBlockHandler::MESSAGE, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}', 'handler.pageBlocks.update')
            ->addRequestExecutor(UpdatePageBlockHandler::MESSAGE, 'handler.pageBlocks.update')
            ->addResponseGenerator(UpdatePageBlockHandler::MESSAGE, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}', 'handler.pageBlocks.delete')
            ->addRequestExecutor(DeletePageBlockHandler::MESSAGE, 'handler.pageBlocks.delete')
            ->addResponseGenerator(DeletePageBlockHandler::MESSAGE, 'responseGenerator.pageBlocks.single');
    }

    public function configureRepositories(Container $container)
    {
        $container['repository.pages'] = function (Container $container) {
            return new PageRepository($container['db'], $container['repository.objects']);
        };
    }
}
