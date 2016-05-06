<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Response\ValidationFailedException;
use Spot\SiteContent\BlockType\VimeoBlockType;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  VimeoBlockType */
class VimeoBlockTypeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(VimeoBlockType::class);
    }

    public function it_can_create_a_new_block(Page $page, PageStatusValue $status)
    {
        $location = 'restaurantAtTheEndOfTheUniverse';
        $sortOrder = 42;
        $block = $this->newBlock($page, $location, $sortOrder, $status);
        $block->shouldHaveType(PageBlock::class);
        $block->getUuid()->shouldHaveType(UuidInterface::class);
        $block->getType()->shouldReturn('vimeo');
        $block->getPage()->shouldReturn($page);
        $block->getParameters()->shouldHaveKey('vimeoUrl');
        $block->getLocation()->shouldReturn($location);
        $block->getSortOrder()->shouldReturn($sortOrder);
        $block->getStatus()->shouldReturn($status);
    }

    public function it_can_validate_a_block(PageBlock $block, RequestInterface $request)
    {
        $block->getParameters()->willReturn(['vimeoUrl' => 'https://vimeo.com/s0m3Ur1']);
        $this->validate($block, $request)->shouldReturn(null);
    }

    public function it_can_invalidate_a_block(PageBlock $block, RequestInterface $request)
    {
        $request->getAcceptContentType()->willReturn('*/*');
        $block->getParameters()->willReturn(['vimeoUrl' => 'https://www.youtube.com/s0m3Ur1']);
        $this->shouldThrow(ValidationFailedException::class)->duringValidate($block, $request);
    }
}
