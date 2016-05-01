<?php declare(strict_types = 1);

namespace Spot\FileManager;

use League\Flysystem\Filesystem;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Spot\Api\ApplicationServiceProvider;
use Spot\Api\Response\Generator\MultiEntityGenerator;
use Spot\Api\Response\Generator\SingleEntityGenerator;
use Spot\Api\Response\ResponseInterface;
use Spot\Api\ServiceProvider\RepositoryProviderInterface;
use Spot\Api\ServiceProvider\RoutingProviderInterface;
use Spot\FileManager\Handler\DeleteFileHandler;
use Spot\FileManager\Handler\GetDirectoryListingHandler;
use Spot\FileManager\Handler\GetFileHandler;
use Spot\FileManager\Handler\MoveFileHandler;
use Spot\FileManager\Handler\RenameFileHandler;
use Spot\FileManager\Handler\UploadFileHandler;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Serializer\DirectoryListingSerializer;
use Spot\FileManager\Serializer\FileSerializer;

class FileManagerServiceProvider implements
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
        $container['fileStorage'] = function (Container $container) {
            return new Filesystem($container['fileManager.adapter']);
        };

        $container['fileManager.helper'] = function (Container $container) {
            return new FileManagerHelper();
        };
    }

    public function registerRepositories(Container $container)
    {
        $container['repository.files'] = function (Container $container) {
            return new FileRepository($container['fileStorage'], $container['db'], $container['repository.objects']);
        };
    }

    public function registerRouting(Container $container, ApplicationServiceProvider $builder)
    {
        // Files API Calls
        $container['handler.files.upload'] = function (Container $container) {
            return new UploadFileHandler($container['repository.files'], $container['logger']);
        };
        $container['handler.files.get'] = function (Container $container) {
            return new GetFileHandler($container['repository.files'], $container['logger']);
        };
        $container['handler.files.getDirectory'] = function (Container $container) {
            return new GetDirectoryListingHandler($container['repository.files'], $container['logger']);
        };
        $container['handler.files.delete'] = function (Container $container) {
            return new DeleteFileHandler($container['repository.files'], $container['logger']);
        };
        $container['handler.files.rename'] = function (Container $container) {
            return new RenameFileHandler(
                $container['repository.files'], $container['fileManager.helper'], $container['logger']
            );
        };
        $container['handler.files.move'] = function (Container $container) {
            return new MoveFileHandler(
                $container['repository.files'], $container['fileManager.helper'], $container['logger']
            );
        };

        // Response Generators for both
        $container['responseGenerator.files.single'] = function (Container $container) {
            return new SingleEntityGenerator(new FileSerializer(), null, $container['logger']);
        };
        $container['responseGenerator.files.multi'] = function (Container $container) {
            return new MultiEntityGenerator(new FileSerializer(), null, $container['logger']);
        };
        $container['responseGenerator.directoryListing'] = function (Container $container) {
            return new SingleEntityGenerator(new DirectoryListingSerializer(), null, $container['logger']);
        };

        // Configure ApiBuilder to use Handlers & Response Generators
        $builder
            ->addParser('POST', $this->uriSegment . '/f/{path:.+}', 'handler.files.upload')
            ->addExecutor(UploadFileHandler::MESSAGE, 'handler.files.upload')
            ->addGenerator(UploadFileHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.files.multi');
        $builder
            ->addParser('GET', $this->uriSegment . '/f/{path:.+}', 'handler.files.get')
            ->addExecutor(GetFileHandler::MESSAGE, 'handler.files.get')
            ->addGenerator(GetFileHandler::MESSAGE, '*/*', 'handler.files.get');
        $builder
            ->addParser('GET', $this->uriSegment . '/d/{path:.*}', 'handler.files.getDirectory')
            ->addExecutor(GetDirectoryListingHandler::MESSAGE, 'handler.files.getDirectory')
            ->addGenerator(GetDirectoryListingHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.directoryListing');
        $builder
            ->addParser('DELETE', $this->uriSegment . '/f/{path:.+}', 'handler.files.delete')
            ->addExecutor(DeleteFileHandler::MESSAGE, 'handler.files.delete')
            ->addGenerator(DeleteFileHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.files.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/rename/{path:.+}', 'handler.files.rename')
            ->addExecutor(RenameFileHandler::MESSAGE, 'handler.files.rename')
            ->addGenerator(RenameFileHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.files.single');
        $builder
            ->addParser('PATCH', $this->uriSegment . '/move/{path:.+}', 'handler.files.move')
            ->addExecutor(MoveFileHandler::MESSAGE, 'handler.files.move')
            ->addGenerator(MoveFileHandler::MESSAGE, self::JSON_API_CT, 'responseGenerator.files.single');
    }
}
