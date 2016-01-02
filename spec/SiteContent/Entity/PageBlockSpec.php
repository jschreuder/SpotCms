<?php

namespace spec\Spot\SiteContent\Entity;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageBlock */
class PageBlockSpec extends ObjectBehavior
{
    /** @var  UuidInterface */
    private $uuid;

    /** @var  Page */
    private $page;

    /** @var  string */
    private $type;

    /** @var  array */
    private $parameters;

    /** @var  string */
    private $location;

    /** @var  int */
    private $sortOrder;

    /** @var  PageStatusValue */
    private $status;

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function let($page)
    {
        $this->uuid = Uuid::uuid4();
        $this->page = $page;
        $this->type = 'block-type';
        $this->parameters = ['answer' => 42];
        $this->location = 'main';
        $this->sortOrder = 2;
        $this->status = PageStatusValue::get('published');
        $this->beConstructedWith(
            $this->uuid, $this->page, $this->type, $this->parameters, $this->location, $this->sortOrder, $this->status
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageBlock::class);
    }

    public function it_can_gets_its_uuid()
    {
        $this->getUuid()->shouldReturn($this->uuid);
    }

    public function it_can_gets_its_page()
    {
        $this->getPage()->shouldReturn($this->page);
    }

    public function it_can_gets_its_type()
    {
        $this->getType()->shouldReturn($this->type);
    }

    public function it_can_gets_its_parameters()
    {
        $this->getParameters()->shouldReturn($this->parameters);
    }

    public function it_can_gets_its_location()
    {
        $this->getLocation()->shouldReturn($this->location);
    }

    public function it_can_gets_its_sort_order()
    {
        $this->getSortOrder()->shouldReturn($this->sortOrder);
    }

    public function it_can_change_its_sort_order()
    {
        $sortOrder = 5;
        $this->setSortOrder($sortOrder)->shouldReturn($this);
        $this->getSortOrder()->shouldReturn($sortOrder);
    }

    public function it_can_change_its_status()
    {
        $status = PageStatusValue::get('concept');
        $this->setStatus($status)->shouldReturn($this);
        $this->getStatus()->shouldReturn($status);
    }

    public function it_can_modify_parameters_using_ArrayAccess()
    {
        $this->offsetExists('answer')->shouldReturn(true);
        $this->offsetExists('thx')->shouldReturn(false);
        $this['thx'] = 1138;
        $this->offsetExists('thx')->shouldReturn(true);
        $this->offsetGet('thx')->shouldReturn(1138);
        unset($this['thx']);
        $this->offsetExists('thx')->shouldReturn(false);

        $this->shouldThrow(\OutOfBoundsException::class)->duringOffsetGet('thx');
    }
}
