<?php

namespace spec\Spot\SiteContent\Schema;

use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Schema\PageBlockSchema;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageBlockSchema */
class PageBlockSchemaSpec extends ObjectBehavior
{
    /** @var  FactoryInterface */
    private $factory;

    /** @var  PageBlock */
    private $block;

    public function let(Page $page)
    {
        $this->block = (new PageBlock(
                Uuid::uuid4(),
                $page->getWrappedObject(),
                'type',
                ['answer' => 42],
                'main',
                42,
                PageStatusValue::get(PageStatusValue::PUBLISHED)
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable())
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageBlockSerializer::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType()->shouldReturn(PageBlock::TYPE);
    }

    public function it_can_give_an_entities_id()
    {
        $this->getId($this->block)->shouldReturn($this->block->getUuid()->toString());
    }

    public function it_errors_when_get_id_given_non_pageBlock_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetId(new \stdClass());
    }

    public function it_can_transform_pageBlock_to_array(ContextInterface $context)
    {
        $attributes = $this->getAttributes($this->block, $context);
        $attributes['type']->shouldBe($this->block->getType());
        $attributes['parameters']->shouldBe($this->block->getParameters());
        $attributes['location']->shouldBe($this->block->getLocation());
        $attributes['sort_order']->shouldBe($this->block->getSortOrder());
        $attributes['status']->shouldBe($this->block->getStatus()->toString());
        $attributes['meta']->shouldBe([
            'created' => $this->block->metaDataGetCreatedTimestamp()->format('c'),
            'updated' => $this->block->metaDataGetUpdatedTimestamp()->format('c'),
        ]);
    }

    public function it_errors_when_get_attributes_given_non_pageBlock_entity(ContextInterface $context)
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass(), $context);
    }

    public function it_can_provide_pageBlock_relationship(ContextInterface $context)
    {
        $this->getRelationships($this->block, $context)->shouldReturn([]);
    }

    public function it_errors_when_get_relationship_given_non_pageBlock_entity(ContextInterface $context)
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationships(new \stdClass(), $context);
    }
}
