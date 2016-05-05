<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\BlockType\VimeoBlockType;

/** @mixin  VimeoBlockType */
class VimeoBlockTypeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(VimeoBlockType::class);
    }
}
