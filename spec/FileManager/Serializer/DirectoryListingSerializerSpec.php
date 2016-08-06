<?php

namespace spec\Spot\FileManager\Serializer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\FileManager\Serializer\DirectoryListingSerializer;

/** @mixin  DirectoryListingSerializer */
class DirectoryListingSerializerSpec extends ObjectBehavior
{
    private $listing = [
        'path' => '/path/To/files',
        'directories' => [],
        'files' => [],
    ];

    public function it_is_initializable()
    {
        $this->shouldHaveType(DirectoryListingSerializer::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType($this->listing)->shouldReturn('directoryListings');
    }

    public function it_can_give_an_entities_id()
    {
        $this->getId($this->listing)->shouldReturn($this->listing['path']);
    }

    public function it_can_return_its_attributes()
    {
        $copy = $this->listing;
        unset($copy['path']);

        $this->getAttributes($this->listing)->shouldReturn($copy);
    }

    public function it_does_not_have_relationships()
    {
        $this->shouldThrow(\OutOfBoundsException::class)->duringGetRelationship($this->listing, 'nope');
    }

    public function it_can_get_links()
    {
        $this->getLinks(new \stdClass())->shouldReturn([]);
    }

    public function it_can_get_meta()
    {
        $this->getMeta(new \stdClass())->shouldReturn([]);
    }
}
