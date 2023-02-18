<?php

namespace spec\Spot\SiteContent\JsonConverter;

use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\JsonConverter\PageJsonConverter;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageJsonConverter */
class PageJsonConverterSpec extends ObjectBehavior
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
        $this->shouldHaveType(PageJsonConverter::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType()->shouldReturn(Page::TYPE);
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
        $this->getRelationships($this->page)->shouldBeArray();
    }

    public function it_errors_when_get_relationship_given_non_page_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationships(new \stdClass());
    }
}
