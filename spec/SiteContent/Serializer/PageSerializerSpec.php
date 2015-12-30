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

    public function it_isInitializable()
    {
        $this->shouldHaveType(PageSerializer::class);
    }

    public function it_canGiveItsEntityType()
    {
        $this->getType($this->page)->shouldReturn(Page::TYPE);
    }

    public function it_canGiveAnEntitiesId()
    {
        $this->getId($this->page)->shouldReturn($this->page->getUuid()->toString());
    }

    public function it_errorsWhenGetIdGivenNonPageEntity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetId(new \stdClass());
    }

    public function it_canTransformPageToArray()
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

    public function it_errorsWhenGetAttributesGivenNonPageEntity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass());
    }

    public function it_canProvideBlocksRelationship()
    {
        $this->page->setBlocks([]);
        $this->getRelationship($this->page, PageBlock::TYPE)
            ->shouldHaveType(Relationship::class);
    }

    public function it_errorsWhenGetRelationshipAsksForUnknownRelation()
    {
        $this->shouldThrow(\OutOfBoundsException::class)->duringGetRelationship($this->page, 'nope');
    }

    public function it_errorsWhenGetRelationshipGivenNonPageEntity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationship(new \stdClass(), PageBlock::TYPE);
    }
}
