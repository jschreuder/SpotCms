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
use Spot\SiteContent\ApiCall\AddPageBlockApiCall;
use Spot\SiteContent\ApiCall\CreatePageApiCall;
use Spot\SiteContent\ApiCall\DeletePageApiCall;
use Spot\SiteContent\ApiCall\DeletePageBlockApiCall;
use Spot\SiteContent\ApiCall\GetPageApiCall;
use Spot\SiteContent\ApiCall\GetPageBlockApiCall;
use Spot\SiteContent\ApiCall\ListPagesApiCall;
use Spot\SiteContent\ApiCall\UpdatePageApiCall;
use Spot\SiteContent\ApiCall\UpdatePageBlockApiCall;
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
        $container['apiCall.pages.create'] = function (Container $container) {
            return new CreatePageApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.list'] = function (Container $container) {
            return new ListPagesApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.get'] = function (Container $container) {
            return new GetPageApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.update'] = function (Container $container) {
            return new UpdatePageApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pages.delete'] = function (Container $container) {
            return new DeletePageApiCall($container['repository.pages'], $container['logger']);
        };

        // PageBlocks API Calls
        $container['apiCall.pageBlocks.create'] = function (Container $container) {
            return new AddPageBlockApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pageBlocks.get'] = function (Container $container) {
            return new GetPageBlockApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pageBlocks.update'] = function (Container $container) {
            return new UpdatePageBlockApiCall($container['repository.pages'], $container['logger']);
        };
        $container['apiCall.pageBlocks.delete'] = function (Container $container) {
            return new DeletePageBlockApiCall($container['repository.pages'], $container['logger']);
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

        // Configure ApiBuilder to use ApiCalls & Response Generators
        $builder
            ->addParser('POST', $this->uriSegment, 'apiCall.pages.create')
            ->addRequestExecutor(CreatePageApiCall::MESSAGE, 'apiCall.pages.create')
            ->addResponseGenerator(CreatePageApiCall::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('GET', $this->uriSegment, 'apiCall.pages.list')
            ->addRequestExecutor(ListPagesApiCall::MESSAGE, 'apiCall.pages.list')
            ->addResponseGenerator(ListPagesApiCall::MESSAGE, 'responseGenerator.pages.multi');
        $builder
            ->addParser('GET', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'apiCall.pages.get')
            ->addRequestExecutor(GetPageApiCall::MESSAGE, 'apiCall.pages.get')
            ->addResponseGenerator(GetPageApiCall::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'apiCall.pages.update')
            ->addRequestExecutor(UpdatePageApiCall::MESSAGE, 'apiCall.pages.update')
            ->addResponseGenerator(UpdatePageApiCall::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/{uuid:[0-9a-z\-]+}', 'apiCall.pages.delete')
            ->addRequestExecutor(DeletePageApiCall::MESSAGE, 'apiCall.pages.delete')
            ->addResponseGenerator(DeletePageApiCall::MESSAGE, 'responseGenerator.pages.single');
        $builder
            ->addParser('POST', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks', 'apiCall.pageBlocks.create')
            ->addRequestExecutor(AddPageBlockApiCall::MESSAGE, 'apiCall.pageBlocks.create')
            ->addResponseGenerator(AddPageBlockApiCall::MESSAGE, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('GET', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}', 'apiCall.pageBlocks.get')
            ->addRequestExecutor(GetPageBlockApiCall::MESSAGE, 'apiCall.pageBlocks.get')
            ->addResponseGenerator(GetPageBlockApiCall::MESSAGE, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}', 'apiCall.pageBlocks.update')
            ->addRequestExecutor(UpdatePageBlockApiCall::MESSAGE, 'apiCall.pageBlocks.update')
            ->addResponseGenerator(UpdatePageBlockApiCall::MESSAGE, 'responseGenerator.pageBlocks.single');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/{page_uuid:[0-9a-z\-]+}/blocks/{uuid:[0-9a-z\-]+}', 'apiCall.pageBlocks.delete')
            ->addRequestExecutor(DeletePageBlockApiCall::MESSAGE, 'apiCall.pageBlocks.delete')
            ->addResponseGenerator(DeletePageBlockApiCall::MESSAGE, 'responseGenerator.pageBlocks.single');
    }

    public function configureRepositories(Container $container)
    {
        $container['repository.pages'] = function (Container $container) {
            return new PageRepository($container['db'], $container['repository.objects']);
        };
    }
}
