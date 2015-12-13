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
use Spot\Common\ApiBuilder\ExecutorFactoryInterface;
use Spot\Common\ApiBuilder\GeneratorFactoryInterface;
use Spot\Common\ApiBuilder\HttpRequestParserFactoryInterface;

class ApiBuilder implements
    HttpRequestParserFactoryInterface,
    ExecutorFactoryInterface,
    GeneratorFactoryInterface
{
    /** @var  Container */
    private $container;

    /** @var  \Spot\Api\Request\HttpRequestParser\HttpRequestParserBus */
    private $router;

    /** @var  RouteCollector */
    private $routeCollector;

    /** @var  ExecutorBus */
    private $requestBus;

    /** @var  GeneratorBus */
    private $responseBus;

    public function __construct(
        Container $container,
        HttpRequestParserBus $router,
        RouteCollector $routeCollector,
        ExecutorBus $requestBus,
        GeneratorBus $responseBus,
        array $modules
    ) {
        $this->container = $container;
        $this->router = $router;
        $this->routeCollector = $routeCollector;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;

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

    public function addRequestExecutor(string $requestName, string $executor) : self
    {
        $this->requestBus->setExecutor($requestName, $executor);
        return $this;
    }

    public function addResponseGenerator(string $responseName, string $contentType, string $generator) : self
    {
        $this->responseBus->setGenerator($responseName, $contentType, $generator);
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
        return $this->requestBus;
    }

    /** {@inheritdoc} */
    public function getGenerator() : GeneratorInterface
    {
        return $this->responseBus;
    }
}
