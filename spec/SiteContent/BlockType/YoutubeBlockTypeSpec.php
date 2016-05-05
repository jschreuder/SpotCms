<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\BlockType\YoutubeBlockType;

/** @mixin  YoutubeBlockType */
class YoutubeBlockTypeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(YoutubeBlockType::class);
    }
}
