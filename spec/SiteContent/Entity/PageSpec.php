<?php

namespace spec\Spot\SiteContent\Entity;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\SiteContent\Entity\Page;
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

    public function it_isInitializable()
    {
        $this->shouldHaveType(Page::class);
    }

    public function it_canGetItsUuid()
    {
        $this->getUuid()->shouldReturn($this->uuid);
    }

    public function it_canGetItsTitle()
    {
        $this->getTitle()->shouldReturn($this->title);
    }

    public function it_canChangeItsTitle()
    {
        $newTitle = 'New Page Title';
        $this->setTitle($newTitle)->shouldReturn($this);
        $this->getTitle()->shouldReturn($newTitle);
    }

    public function it_canGetItsSlug()
    {
        $this->getSlug()->shouldReturn($this->slug);
    }

    public function it_canChangeItsSlug()
    {
        $newSlug = 'slug';
        $this->setSlug($newSlug)->shouldReturn($this);
        $this->getSlug()->shouldReturn($newSlug);
    }

    public function it_canGetItsShortTitle()
    {
        $this->getShortTitle()->shouldReturn($this->shortTitle);
    }

    public function it_canChangeItsShortTitle()
    {
        $newTitle = 'Page';
        $this->setShortTitle($newTitle)->shouldReturn($this);
        $this->getShortTitle()->shouldReturn($newTitle);
    }

    public function it_canGetItsParentsUuid()
    {
        $this->getParentUuid()->shouldReturn($this->parentUuid);
    }

    public function it_canGetItsSortOrder()
    {
        $this->getSortOrder()->shouldReturn($this->sortOrder);
    }

    public function it_canChangeItsSortOrder()
    {
        $newSortOrder = 1138;
        $this->setSortOrder($newSortOrder)->shouldReturn($this);
        $this->getSortOrder()->shouldReturn($newSortOrder);
    }

    public function it_canGetItsStatus()
    {
        $this->getStatus()->shouldReturn($this->status);
    }

    public function it_canChangeItsStatus()
    {
        $newStatus = PageStatusValue::get('published');
        $this->setStatus($newStatus)->shouldReturn($this);
        $this->getStatus()->shouldReturn($newStatus);
    }
}
