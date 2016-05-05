<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\BlockType\RssFeedBlockType;

/** @mixin RssFeedBlockType */
class RssFeedBlockTypeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RssFeedBlockType::class);
    }
}
