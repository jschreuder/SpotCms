<?php

namespace spec\Spot\SiteContent\BlockType;

use jschreuder\Middle\Exception\ValidationFailedException;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\UuidInterface;
use Spot\SiteContent\BlockType\RssFeedBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin RssFeedBlockType */
class RssFeedBlockTypeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RssFeedBlockType::class);
    }

    public function it_can_create_a_new_block(Page $page, PageStatusValue $status)
    {
        $location = 'restaurantAtTheEndOfTheUniverse';
        $sortOrder = 42;
        $block = $this->newBlock($page, $location, $sortOrder, $status);
        $block->shouldHaveType(PageBlock::class);
        $block->getUuid()->shouldHaveType(UuidInterface::class);
        $block->getType()->shouldReturn('rssFeed');
        $block->getPage()->shouldReturn($page);
        $block->getParameters()->shouldHaveKey('feedUrl');
        $block->getLocation()->shouldReturn($location);
        $block->getSortOrder()->shouldReturn($sortOrder);
        $block->getStatus()->shouldReturn($status);
    }

    public function it_can_validate_a_block(PageBlock $block, ServerRequestInterface $request)
    {
        $block->getParameters()->willReturn(['feedUrl' => 'https://feed.url/nonsense']);
        $this->validate($block, $request)->shouldReturn(null);
    }

    public function it_can_invalidate_a_block(PageBlock $block, ServerRequestInterface $request)
    {
        $block->getParameters()->willReturn(['feedUrl' => 'not-a-url']);
        $this->shouldThrow(ValidationFailedException::class)->duringValidate($block, $request);
    }
}
