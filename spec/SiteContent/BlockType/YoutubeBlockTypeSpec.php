<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Response\ValidationFailedException;
use Spot\SiteContent\BlockType\YoutubeBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  YoutubeBlockType */
class YoutubeBlockTypeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(YoutubeBlockType::class);
    }

    public function it_can_create_a_new_block(Page $page, PageStatusValue $status)
    {
        $location = 'restaurantAtTheEndOfTheUniverse';
        $sortOrder = 42;
        $block = $this->newBlock($page, $location, $sortOrder, $status);
        $block->shouldHaveType(PageBlock::class);
        $block->getUuid()->shouldHaveType(UuidInterface::class);
        $block->getType()->shouldReturn('youtube');
        $block->getPage()->shouldReturn($page);
        $block->getParameters()->shouldHaveKey('youtubeUrl');
        $block->getLocation()->shouldReturn($location);
        $block->getSortOrder()->shouldReturn($sortOrder);
        $block->getStatus()->shouldReturn($status);
    }

    public function it_can_validate_a_block(PageBlock $block, RequestInterface $request)
    {
        $block->getParameters()->willReturn(['youtubeUrl' => 'https://www.youtube.com/watch?v=s0m3Ur1']);
        $this->validate($block, $request)->shouldReturn(null);
    }

    public function it_can_invalidate_a_block(PageBlock $block, RequestInterface $request)
    {
        $request->getAcceptContentType()->willReturn('*/*');
        $block->getParameters()->willReturn(['youtubeUrl' => 'https://vimeo.com/s0m3Ur1']);
        $this->shouldThrow(ValidationFailedException::class)->duringValidate($block, $request);
    }
}
