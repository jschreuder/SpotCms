<?php

namespace spec\Spot\SiteContent\Serializer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Serializer\PageBlockSerializer;
use Spot\SiteContent\Value\PageStatusValue;
use Tobscure\JsonApi\Relationship;

/** @mixin  PageBlockSerializer */
class PageBlockSerializerSpec extends ObjectBehavior
{
    /** @var  PageBlock */
    private $block;

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function let($page)
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

    public function it_canGiveItsEntityType()
    {
        $this->getType($this->block)->shouldReturn(PageBlock::TYPE);
    }

    public function it_canGiveAnEntitiesId()
    {
        $this->getId($this->block)->shouldReturn($this->block->getUuid()->toString());
    }

    public function it_errorsWhenGetIdGivenNonPageEntity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetId(new \stdClass());
    }

    public function it_canTransformPageToArray()
    {
        $attributes = $this->getAttributes($this->block);
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

    public function it_errorsWhenGetAttributesGivenNonPageEntity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass());
    }

    public function it_canProvideBlocksRelationship()
    {
        $this->getRelationship($this->block, Page::TYPE)
            ->shouldHaveType(Relationship::class);
    }

    public function it_errorsWhenGetRelationshipAsksForUnknownRelation()
    {
        $this->shouldThrow(\OutOfBoundsException::class)->duringGetRelationship($this->block, 'nope');
    }

    public function it_errorsWhenGetRelationshipGivenNonPageEntity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationship(new \stdClass(), Page::TYPE);
    }
}
