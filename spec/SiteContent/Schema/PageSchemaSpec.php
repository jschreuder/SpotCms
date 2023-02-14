<?php

namespace spec\Spot\SiteContent\Schema;

use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Schema\PageSchema;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageSchema */
class PageSchemaSpec extends ObjectBehavior
{
    /** @var  FactoryInterface */
    private $factory;

    /** @var  Page */
    private $page;

    public function let(FactoryInterface $factory)
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
        $this->factory = $factory;
        $this->beConstructedWith($factory);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageSchema::class);
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

    public function it_can_transform_page_to_array(ContextInterface $context)
    {
        $attributes = $this->getAttributes($this->page, $context);
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

    public function it_errors_when_get_attributes_given_non_page_entity(ContextInterface $context)
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass(), $context);
    }

    public function it_can_provide_blocks_relationship(ContextInterface $context)
    {
        $this->page->setBlocks([]);
        $this->getRelationships($this->page, $context)->shouldBeArray();
    }

    public function it_errors_when_get_relationship_given_non_page_entity(ContextInterface $context)
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationships(new \stdClass(), $context);
    }
}
