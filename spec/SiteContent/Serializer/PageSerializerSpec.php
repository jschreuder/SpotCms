<?php

namespace spec\Spot\SiteContent\Serializer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Serializer\PageSerializer;
use Spot\SiteContent\Value\PageStatusValue;
use Tobscure\JsonApi\Relationship;

/** @mixin  PageSerializer */
class PageSerializerSpec extends ObjectBehavior
{
    /** @var  Page */
    private $page;

    public function let()
    {
        $this->page = (new Page(
                Uuid::uuid4(),
                'Page Title',
                'page_title',
                'Title',
                Uuid::uuid4(),
                42,
                PageStatusValue::get(PageStatusValue::PUBLISHED)
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable())
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageSerializer::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType($this->page)->shouldReturn(Page::TYPE);
    }

    public function it_can_give_an_entities_id()
    {
        $this->getId($this->page)->shouldReturn($this->page->getUuid()->toString());
    }

    public function it_errors_when_get_id_given_non_page_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetId(new \stdClass());
    }

    public function it_can_transform_page_to_array()
    {
        $attributes = $this->getAttributes($this->page);
        $attributes['title']->shouldBe($this->page->getTitle());
        $attributes['slug']->shouldBe($this->page->getSlug());
        $attributes['short_title']->shouldBe($this->page->getShortTitle());
        $attributes['parent_uuid']->shouldBe($this->page->getParentUuid()->toString());
        $attributes['sort_order']->shouldBe($this->page->getSortOrder());
        $attributes['status']->shouldBe($this->page->getStatus()->toString());
        $attributes['meta']->shouldBe([
            'created' => $this->page->metaDataGetCreatedTimestamp()->format('c'),
            'updated' => $this->page->metaDataGetUpdatedTimestamp()->format('c'),
        ]);
    }

    public function it_errors_when_get_attributes_given_non_page_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass());
    }

    public function it_can_provide_blocks_relationship()
    {
        $this->page->setBlocks([]);
        $this->getRelationship($this->page, PageBlock::TYPE)
            ->shouldHaveType(Relationship::class);
    }

    public function it_errors_when_get_relationship_asks_for_unknown_relation()
    {
        $this->shouldThrow(\OutOfBoundsException::class)->duringGetRelationship($this->page, 'nope');
    }

    public function it_errors_when_get_relationship_given_non_page_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationship(new \stdClass(), PageBlock::TYPE);
    }
}
