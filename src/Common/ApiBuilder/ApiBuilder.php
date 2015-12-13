<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use Pimple\Container;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserBus;
use Spot\Api\Request\Executor\ExecutorBus;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Response\Generator\GeneratorBus;
use Spot\Api\Response\Generator\GeneratorInterface;

class ApiBuilder implements
    HttpRequestParserFactoryInterface,
    ExecutorFactoryInterface,
    GeneratorFactoryInterface
{
    /** @var  Container */
    private $container;

    /** @var  HttpRequestParserBus */
    private $router;

    /** @var  RouteCollector */
    private $routeCollector;

    /** @var  ExecutorBus */
    private $executorBus;

    /** @var  GeneratorBus */
    private $generatorBus;

    public function __construct(
        Container $container,
        HttpRequestParserBus $router,
        RouteCollector $routeCollector,
        ExecutorBus $executorBus,
        GeneratorBus $generatorBus,
        array $modules
    ) {
        $this->container = $container;
        $this->router = $router;
        $this->routeCollector = $routeCollector;
        $this->executorBus = $executorBus;
        $this->generatorBus = $generatorBus;

        foreach ($modules as $module) {
            $this->addModule($module);
        }
    }

    /**
     * @param   RouterBuilderInterface|RepositoryBuilderInterface $module
     * @return  void
     */
    public function addModule($module)
    {
        if ($module instanceof RouterBuilderInterface) {
            $module->configureRouting($this->container, $this);
        }
        if ($module instanceof RepositoryBuilderInterface) {
            $module->configureRepositories($this->container);
        }
    }

    public function addParser(string $method, string $path, string $httpRequestParser) : self
    {
        $this->routeCollector->addRoute($method, $path, $httpRequestParser);
        return $this;
    }

    public function addExecutor(string $requestName, string $executor) : self
    {
        $this->executorBus->setExecutor($requestName, $executor);
        return $this;
    }

    public function addGenerator(string $responseName, string $contentType, string $generator) : self
    {
        $this->generatorBus->setGenerator($responseName, $contentType, $generator);
        return $this;
    }

    /** {@inheritdoc} */
    public function getHttpRequestParser() : HttpRequestParserInterface
    {
        return $this->router->setRouter(new GroupCountBasedDispatcher($this->routeCollector->getData()));
    }

    /** {@inheritdoc} */
    public function getExecutor() : ExecutorInterface
    {
        return $this->executorBus;
    }

    /** {@inheritdoc} */
    public function getGenerator() : GeneratorInterface
    {
        return $this->generatorBus;
    }
}
