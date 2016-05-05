<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\SiteContent\BlockType\HtmlContentBlockType;

/** @mixin  HtmlContentBlockType */
class HtmlContentBlockTypeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(HtmlContentBlockType::class);
    }
}
