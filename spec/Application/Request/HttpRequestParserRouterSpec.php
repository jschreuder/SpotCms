<?php

namespace spec\Spot\Api\Application\Request;

use PhpSpec\ObjectBehavior;
use Pimple\Container;
use Prophecy\Argument;
use Spot\Api\Application\Request\HttpRequestParserRouter;

class HttpRequestParserRouterSpec extends ObjectBehavior
{
    /** @var  Container */
    private $container;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param   \Psr\Log\LoggerInterface $logger
     */
    public function let($logger)
    {
        $this->container = new Container();
        $this->logger = $logger;
        $this->beConstructedWith($this->container, $logger);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(HttpRequestParserRouter::class);
    }

    public function it_specIsIncomplete()
    {
    }
}
