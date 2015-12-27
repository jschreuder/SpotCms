<?php

namespace spec\Spot\Common\ApiServiceProvider;

use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Common\ApiServiceProvider\ApiServiceProvider;

/** @mixin  ApiServiceProvider */
class ApiServiceProviderSpec extends ObjectBehavior
{
    /** @var  \Pimple\Container */
    private $container;

    /** @var  \Spot\Api\Request\HttpRequestParser\HttpRequestParserBus */
    private $router;

    /** @var  \FastRoute\RouteCollector */
    private $routeCollector;

    /** @var  \Spot\Api\Request\Executor\ExecutorBus */
    private $executorBus;

    /** @var  \Spot\Api\Response\Generator\GeneratorBus */
    private $generatorBus;

    /** @var  object[] */
    private $modules;

    /**
     * @param  \Pimple\Container $container
     * @param  \Spot\Api\Request\HttpRequestParser\HttpRequestParserBus $router
     * @param  \FastRoute\RouteCollector $routeCollector
     * @param  \Spot\Api\Request\Executor\ExecutorBus $executorBus
     * @param  \Spot\Api\Response\Generator\GeneratorBus $generatorBus
     */
    public function let($container, $router, $routeCollector, $executorBus, $generatorBus)
    {
        $this->container = $container;
        $this->router = $router;
        $this->routeCollector = $routeCollector;
        $this->executorBus = $executorBus;
        $this->generatorBus = $generatorBus;
        $this->modules = [new \stdClass()];

        $this->beConstructedWith($container, $router, $routeCollector, $executorBus, $generatorBus, $this->modules);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(ApiServiceProvider::class);
    }

    /**
     * @param  \Pimple\ServiceProviderInterface $serviceModule
     * @param  \Spot\Common\ApiServiceProvider\RoutingProviderInterface $routeModule
     * @param  \Spot\Common\ApiServiceProvider\RepositoryProviderInterface $repoModule
     */
    public function it_canAddModules($serviceModule, $routeModule, $repoModule)
    {
        $serviceModule->register($this->container)
            ->shouldBeCalled();
        $routeModule->registerRouting($this->container, $this)
            ->shouldBeCalled();
        $repoModule->registerRepositories($this->container)
            ->shouldBeCalled();

        $this->addModule($serviceModule);
        $this->addModule($routeModule);
        $this->addModule($repoModule);
    }

    public function it_canAddARequestParser()
    {
        $method = 'GET';
        $path = '/some/way';
        $parser = 'my.way';
        $this->routeCollector->addRoute($method, $path, $parser)
            ->shouldBeCalled();
        $this->addParser($method, $path, $parser)
            ->shouldReturn($this);
    }

    public function it_canAddAnExecutor()
    {
        $name = 'my.way';
        $executor = 'i.did.it';
        $this->executorBus->setExecutor($name, $executor)
            ->shouldBeCalled();
        $this->addExecutor($name, $executor)
            ->shouldReturn($this);
    }

    public function it_canAddAnGenerator()
    {
        $name = 'my.way';
        $type = 'application/song';
        $generator = 'i.did.it';
        $this->generatorBus->setGenerator($name, $type, $generator)
            ->shouldBeCalled();
        $this->addGenerator($name, $type, $generator)
            ->shouldReturn($this);
    }

    public function it_canReturnTheHttpRequestParserBus()
    {
        $this->router->setRouter(new Argument\Token\TypeToken(GroupCountBasedDispatcher::class))
            ->willReturn($this->router);

        $this->getHttpRequestParser()->shouldReturn($this->router);
    }

    public function it_canReturnTheExecutorBus()
    {
        $this->getExecutor()->shouldReturn($this->executorBus);
    }

    public function it_canReturnTheGeneratorBus()
    {
        $this->getGenerator()->shouldReturn($this->generatorBus);
    }

    /**
     * @param   \Pimple\Container $container
     */
    public function it_canRegisterTheApp($container)
    {
        $container->offsetSet('app', new Argument\Token\TypeToken(\Closure::class))
            ->shouldBeCalled();
        $this->register($container);
    }
}
