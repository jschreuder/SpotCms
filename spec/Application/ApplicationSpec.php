<?php

namespace spec\Spot\Cms\Application;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Cms\Application\Application;

class ApplicationSpec extends ObjectBehavior
{
    /** @var  \Spot\Cms\Application\Request\HttpRequestParserInterface $requestParser */
    private $requestParser;

    /** @var  \Spot\Cms\Application\Request\RequestBusInterface $requestBus */
    private $requestBus;

    /** @var  \Spot\Cms\Application\Response\ResponseBusInterface $responseBus */
    private $responseBus;

    /** @var  \Psr\Log\LoggerInterface $logger */
    private $logger;

    /**
     * @param  \Spot\Cms\Application\Request\HttpRequestParserInterface $requestParser
     * @param  \Spot\Cms\Application\Request\RequestBusInterface $requestBus
     * @param  \Spot\Cms\Application\Response\ResponseBusInterface $responseBus
     * @param  \Psr\Log\LoggerInterface $logger
     */
    public function let($requestParser, $requestBus, $responseBus, $logger)
    {
        $this->requestParser = $requestParser;
        $this->requestBus = $requestBus;
        $this->responseBus = $responseBus;
        $this->logger = $logger;
        $this->beConstructedWith($requestParser, $requestBus, $responseBus, $logger);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(Application::class);
    }
}
