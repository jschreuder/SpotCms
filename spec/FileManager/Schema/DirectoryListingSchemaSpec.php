<?php

namespace spec\Spot\FileManager\Schema;

use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use PhpSpec\ObjectBehavior;
use Spot\FileManager\Schema\DirectoryListingSchema;

/** @mixin  DirectoryListingSchema */
class DirectoryListingSchemaSpec extends ObjectBehavior
{
    /** @var  FactoryInterface */
    private $factory;

    private array $listing = [
        'path' => '/path/To/files',
        'directories' => [],
        'files' => [],
    ];

    public function let(FactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->beConstructedWith($factory);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DirectoryListingSchema::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType()->shouldReturn('directoryListings');
    }

    public function it_can_give_an_entities_id()
    {
        $this->getId($this->listing)->shouldReturn($this->listing['path']);
    }

    public function it_can_return_its_attributes(ContextInterface $context)
    {
        $copy = $this->listing;
        unset($copy['path']);

        $this->getAttributes($this->listing, $context)->shouldReturn($copy);
    }

    public function it_does_not_have_relationships(ContextInterface $context)
    {
        $this->getRelationships($this->listing, $context)->shouldReturn([]);
    }
}
