<?php

namespace spec\Spot\SiteContent\BlockType;

use PhpSpec\ObjectBehavior;
use Spot\SiteContent\BlockType\BlockTypeContainer;
use Spot\SiteContent\BlockType\BlockTypeInterface;

/** @mixin  BlockTypeContainer */
class BlockTypeContainerSpec extends ObjectBehavior
{
    /** @var  BlockTypeInterface */
    private $type;

    /** @var  string */
    private $typeName = 'thisType';

    public function let(BlockTypeInterface $blockType)
    {
        $this->type = $blockType;
        $this->beConstructedWith([$blockType]);

        $this->type->getTypeName()->willReturn($this->typeName);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BlockTypeContainer::class);
    }

    public function it_can_retrieve_a_type()
    {
        $this->getType($this->typeName)->shouldReturn($this->type);
    }

    public function it_can_add_and_retrieve_a_new_type(BlockTypeInterface $blockType)
    {
        $typeName = 'aNewType';
        $blockType->getTypeName()->willReturn($typeName);
        $this->addType($blockType)->shouldReturn($this);
        $this->getType($typeName)->shouldReturn($blockType);
    }

    public function it_cant_retrieve_an_unknown_type()
    {
        $this->shouldThrow(\OutOfBoundsException::class)->duringGetType('the-great-unknown');
    }
}
