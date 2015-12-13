<?php

namespace spec\Spot\Common\ApiBuilder;

use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Common\ApiBuilder\ApiBuilder;

/** @mixin  ApiBuilder */
class ApiBuilderSpec extends ObjectBehavior
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

        $this->beConstructedWith($container, $router, $routeCollector, $executorBus, $generatorBus, []);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(ApiBuilder::class);
    }

    /**
     * @param  \Spot\Common\ApiBuilder\RouterBuilderInterface $routeModule
     * @param  \Spot\Common\ApiBuilder\RepositoryBuilderInterface $repoModule
     */
    public function it_canAddModules($routeModule, $repoModule)
    {
        $routeModule->configureRouting($this->container, $this)
            ->shouldBeCalled();
        $repoModule->configureRepositories($this->container)
            ->shouldBeCalled();

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
}
