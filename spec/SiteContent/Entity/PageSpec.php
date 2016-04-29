<?php

namespace spec\Spot\SiteContent\Entity;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  Page */
class PageSpec extends ObjectBehavior
{
    /** @var  UuidInterface */
    private $uuid;

    /** @var  string */
    private $title;

    /** @var  string */
    private $slug;

    /** @var  string */
    private $shortTitle;

    /** @var  UuidInterface */
    private $parentUuid;

    /** @var  int */
    private $sortOrder;

    /** @var  PageStatusValue */
    private $status;

    public function let()
    {
        $this->uuid = Uuid::uuid4();
        $this->title = 'Page Title';
        $this->slug = 'page_title';
        $this->shortTitle = 'Title';
        $this->parentUuid = Uuid::uuid4();
        $this->sortOrder = 42;
        $this->status = PageStatusValue::get('concept');
        $this->beConstructedWith(
            $this->uuid,
            $this->title,
            $this->slug,
            $this->shortTitle,
            $this->parentUuid,
            $this->sortOrder,
            $this->status
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Page::class);
    }

    public function it_can_get_its_uuid()
    {
        $this->getUuid()->shouldReturn($this->uuid);
    }

    public function it_can_get_its_title()
    {
        $this->getTitle()->shouldReturn($this->title);
    }

    public function it_can_change_its_title()
    {
        $newTitle = 'New Page Title';
        $this->setTitle($newTitle)->shouldReturn($this);
        $this->getTitle()->shouldReturn($newTitle);
    }

    public function it_can_get_its_slug()
    {
        $this->getSlug()->shouldReturn($this->slug);
    }

    public function it_can_change_its_slug()
    {
        $newSlug = 'slug';
        $this->setSlug($newSlug)->shouldReturn($this);
        $this->getSlug()->shouldReturn($newSlug);
    }

    public function it_can_get_its_short_title()
    {
        $this->getShortTitle()->shouldReturn($this->shortTitle);
    }

    public function it_can_change_its_short_title()
    {
        $newTitle = 'Page';
        $this->setShortTitle($newTitle)->shouldReturn($this);
        $this->getShortTitle()->shouldReturn($newTitle);
    }

    public function it_can_get_its_parents_uuid()
    {
        $this->getParentUuid()->shouldReturn($this->parentUuid);
    }

    public function it_can_get_its_sort_order()
    {
        $this->getSortOrder()->shouldReturn($this->sortOrder);
    }

    public function it_can_change_its_sort_order()
    {
        $newSortOrder = 1138;
        $this->setSortOrder($newSortOrder)->shouldReturn($this);
        $this->getSortOrder()->shouldReturn($newSortOrder);
    }

    public function it_can_get_its_status()
    {
        $this->getStatus()->shouldReturn($this->status);
    }

    public function it_can_change_its_status()
    {
        $newStatus = PageStatusValue::get('published');
        $this->setStatus($newStatus)->shouldReturn($this);
        $this->getStatus()->shouldReturn($newStatus);
    }

    public function it_can_get_blocks_set(PageBlock $block1, PageBlock $block2, PageBlock $block3)
    {
        $block1->getSortOrder()->willReturn(1);
        $block2->getSortOrder()->willReturn(2);
        $block3->getSortOrder()->willReturn(3);

        $this->setBlocks([$block3, $block1, $block2, $block1])
            ->shouldReturn($this);

        $blocks = $this->getBlocks();
        $blocks[0]->shouldBe($block1);
        $blocks[1]->shouldBe($block1);
        $blocks[2]->shouldBe($block2);
        $blocks[3]->shouldBe($block3);
    }

    public function it_can_add_and_remove_blocks(PageBlock $block1, PageBlock $block2, PageBlock $block3)
    {
        $uuid1 = Uuid::uuid4();
        $block1->getUuid()->willReturn($uuid1);
        $block1->getSortOrder()->willReturn(1);
        $uuid2 = Uuid::uuid4();
        $block2->getUuid()->willReturn($uuid2);
        $block2->getSortOrder()->willReturn(2);
        $uuid3 = Uuid::uuid4();
        $block3->getUuid()->willReturn($uuid3);
        $block3->getSortOrder()->willReturn(3);

        $this->setBlocks([$block3, $block2]);
        $this->addBlock($block1);
        $this->removeBlock($block2);

        $blocks = $this->getBlocks();
        $blocks[0]->shouldBe($block1);
        $blocks[1]->shouldBe($block3);
    }

    public function it_errors_when_asked_to_remove_non_related_block(
        PageBlock $block1,
        PageBlock $block2,
        PageBlock $block3
    )
    {
        $uuid1 = Uuid::uuid4();
        $block1->getUuid()->willReturn($uuid1);
        $block1->getSortOrder()->willReturn(1);
        $uuid2 = Uuid::uuid4();
        $block2->getUuid()->willReturn($uuid2);
        $block2->getSortOrder()->willReturn(2);
        $uuid3 = Uuid::uuid4();
        $block3->getUuid()->willReturn($uuid3);
        $block3->getSortOrder()->willReturn(3);

        $this->setBlocks([$block3, $block2]);
        $this->shouldThrow(NoResultException::class)->duringRemoveBlock($block1);
    }

    public function it_can_get_a_specific_block(PageBlock $block1, PageBlock $block2, PageBlock $block3)
    {
        $uuid1 = Uuid::uuid4();
        $block1->getUuid()->willReturn($uuid1);
        $block1->getSortOrder()->willReturn(1);
        $uuid2 = Uuid::uuid4();
        $block2->getUuid()->willReturn($uuid2);
        $block2->getSortOrder()->willReturn(2);
        $uuid3 = Uuid::uuid4();
        $block3->getUuid()->willReturn($uuid3);
        $block3->getSortOrder()->willReturn(3);

        $this->setBlocks([$block1, $block2, $block3])
            ->shouldReturn($this);

        $this->getBlockByUuid($uuid2)
            ->shouldReturn($block2);
    }

    public function it_errors_when_a_specific_block_does_not_exist(
        PageBlock $block1,
        PageBlock $block2,
        PageBlock $block3
    )
    {
        $uuid1 = Uuid::uuid4();
        $block1->getUuid()->willReturn($uuid1);
        $block1->getSortOrder()->willReturn(1);
        $uuid2 = Uuid::uuid4();
        $block2->getUuid()->willReturn($uuid2);
        $block2->getSortOrder()->willReturn(2);
        $uuid3 = Uuid::uuid4();
        $block3->getUuid()->willReturn($uuid3);
        $block3->getSortOrder()->willReturn(3);

        $this->setBlocks([$block1, $block2, $block3])
            ->shouldReturn($this);

        $this->shouldThrow(NoResultException::class)->duringGetBlockByUuid(Uuid::uuid4());
    }

    public function it_throws_exception_when_block_methods_are_called_without_blocks(PageBlock $block)
    {
        $this->shouldThrow(\RuntimeException::class)->duringGetBlocks();
        $this->shouldThrow(\RuntimeException::class)->duringAddBlock($block);
        $this->shouldThrow(\RuntimeException::class)->duringRemoveBlock($block);
        $this->shouldThrow(\RuntimeException::class)->duringGetBlockByUuid(Uuid::uuid4());
    }
}
