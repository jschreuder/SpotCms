<?php

namespace spec\Spot\Cms\Application;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Spot\Cms\Application\Application;
use Spot\Cms\Application\Request\HttpRequestParserInterface;
use Spot\Cms\Application\Request\RequestBusInterface;
use Spot\Cms\Application\Response\ResponseBusInterface;

class ApplicationSpec extends ObjectBehavior
{
    /** @var  HttpRequestParserInterface $requestParser */
    private $requestParser;

    /** @var  RequestBusInterface $requestBus */
    private $requestBus;

    /** @var  ResponseBusInterface $responseBus */
    private $responseBus;

    /** @var  LoggerInterface $logger */
    private $logger;

    public function let(
        HttpRequestParserInterface $requestParser,
        RequestBusInterface $requestBus,
        ResponseBusInterface $responseBus,
        LoggerInterface $logger
    ) {
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
