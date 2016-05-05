<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\BlockTypeInterface;

/** @mixin  BlockTypeContainer */
class BlockTypeContainerSpec extends ObjectBehavior
{
    /** @var  BlockTypeInterface */
    private $type;

    public function let(BlockTypeInterface $blockType)
    {
        $this->type = $blockType;
        $this->beConstructedWith([$blockType]);
    }

    public function it_is_initializable()
    {
        $this->type->getTypeName()->willReturn('thisType');
        $this->shouldHaveType(BlockTypeContainer::class);
    }
}
