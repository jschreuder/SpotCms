<?php

namespace spec\Spot\SiteContent\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\Handler\UpdatePageHandler;

/** @mixin  UpdatePageHandler */
class UpdatePageHandlerSpec extends ObjectBehavior
{
    /** @var  \Spot\SiteContent\Repository\PageRepository */
    private $pageRepository;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \Spot\SiteContent\Repository\PageRepository $pageRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function let($pageRepository, $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
        $this->beConstructedWith($pageRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdatePageHandler::class);
    }
}
