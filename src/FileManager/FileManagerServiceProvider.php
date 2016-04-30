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
use Spot\FileManager\Handler\GetFileHandler;
use Spot\FileManager\Handler\UploadFileHandler;
use Spot\FileManager\Repository\FileRepository;
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

        // Response Generators for both
        $container['responseGenerator.files.single'] = function (Container $container) {
            return new SingleEntityGenerator(new FileSerializer(), null, $container['logger']);
        };
        $container['responseGenerator.files.multi'] = function (Container $container) {
            return new MultiEntityGenerator(
                new FileSerializer(),
                function (ResponseInterface $response) : array {
                    $metaData = [
                    ];
                    return $metaData;
                },
                $container['logger']
            );
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
    }
}
