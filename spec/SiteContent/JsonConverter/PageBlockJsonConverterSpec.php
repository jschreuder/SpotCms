<?php

namespace spec\Spot\SiteContent\JsonConverter;

use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\JsonConverter\PageBlockJsonConverter;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageBlockJsonConverter */
class PageBlockJsonConverterSpec extends ObjectBehavior
{
    /** @var  PageBlock */
    private $block;

    /** @var  UuidInterface */
    private $blockUuid;

    /** @var  Page */
    private $page;

    public function let(PageBlock $pageBlock, Page $page)
    {
        $this->block = $pageBlock;
        $this->page = $page;

        $this->block->getUuid()->willReturn($this->blockUuid = Uuid::uuid4());
        $this->block->getPage()->willReturn($page);
        $this->block->getType()->willReturn('type');
        $this->block->getParameters()->willReturn(['answer' => 42]);
        $this->block->getLocation()->willReturn('main');
        $this->block->getSortOrder()->willReturn(42);
        $this->block->getStatus()->willReturn(PageStatusValue::get(PageStatusValue::PUBLISHED));
        $this->block->metaDataGetCreatedTimestamp()->willReturn(new \DateTimeImmutable('2004-02-12T15:19:21+00:00'));
        $this->block->metaDataGetUpdatedTimestamp()->willReturn(new \DateTimeImmutable('2004-02-16T15:19:21+00:00'));
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageBlockJsonConverter::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType()->shouldReturn(PageBlock::TYPE);
    }

    public function it_can_give_an_entities_id()
    {
        $this->getId($this->block)->shouldReturn($this->blockUuid->toString());
    }

    public function it_errors_when_get_id_given_non_pageBlock_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetId(new \stdClass());
    }

    public function it_can_transform_pageBlock_to_array()
    {
        $attributes = $this->getAttributes($this->block);
        $attributes['type']->shouldBe('type');
        $attributes['parameters']->shouldBe(['answer' => 42]);
        $attributes['location']->shouldBe('main');
        $attributes['sort_order']->shouldBe(42);
        $attributes['status']->shouldBe(PageStatusValue::PUBLISHED);
        $attributes['meta']->shouldBe([
            'created' => '2004-02-12T15:19:21+00:00',
            'updated' => '2004-02-16T15:19:21+00:00',
        ]);
    }

    public function it_errors_when_get_attributes_given_non_pageBlock_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass());
    }

    public function it_can_provide_pageBlock_relationship()
    {
        $this->page->getUuid()->willReturn(Uuid::uuid4());
        $this->page->getTitle()->willReturn('A very long title');
        $this->page->getslug()->willReturn('slug_title');
        $this->page->getShortTitle()->willReturn('Short title');
        $this->page->getSortOrder()->willReturn(42);
        $this->page->getStatus()->willReturn(PageStatusValue::get(PageStatusValue::PUBLISHED));
        $this->getRelationships($this->block)->shouldBeArray();
    }

    public function it_errors_when_get_relationship_given_non_pageBlock_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationships(new \stdClass());
    }
}
