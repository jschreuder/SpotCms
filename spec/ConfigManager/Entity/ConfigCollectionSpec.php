<?php

namespace spec\Spot\ConfigManager\Entity;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\ConfigManager\ConfigType\ConfigTypeInterface;
use Spot\ConfigManager\Entity\ConfigCollection;

/** @mixin  ConfigCollection */
class ConfigCollectionSpec extends ObjectBehavior
{
    /** @var  UuidInterface */
    private $uuid;

    /** @var  ConfigTypeInterface */
    private $type;

    /** @var  string */
    private $name = 'testConfig';

    private $items = [
        'val1' => 'one',
        'val2' => 2,
    ];

    public function let(ConfigTypeInterface $type)
    {
        $this->uuid = Uuid::uuid4();
        $this->type = $type;
        $this->beConstructedWith($this->uuid, $type, $this->name);

        $type->getDefaultItems()->willReturn($this->items);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ConfigCollection::class);
    }

    public function it_can_give_its_uuid()
    {
        $this->getUuid()->shouldReturn($this->uuid);
    }

    public function it_can_give_its_type()
    {
        $this->getType()->shouldReturn($this->type);
    }

    public function it_can_give_its_name()
    {
        $this->getName()->shouldReturn($this->name);
    }

    public function it_can_set_and_get_items()
    {
        $this->hasItem('val1')->shouldReturn(true);
        $this->hasItem('nope')->shouldReturn(false);

        $this->getItem('val1')->shouldReturn($this->items['val1']);
        $this->getItem('val2')->shouldReturn($this->items['val2']);

        $this->shouldThrow(\OutOfBoundsException::class)->duringGetItem('nope');
        $this->shouldThrow(\OutOfBoundsException::class)->duringSetItem('nope', true);

        $newValue = 'two';
        $this->setItem('val1', $newValue)->shouldReturn($this);
        $this->getItem('val1')->shouldReturn($newValue);
    }

    public function it_can_get_all_items()
    {
        $this->getItems()->shouldReturn($this->items);
    }

    public function it_can_be_used_as_an_array()
    {
        $this->shouldHaveType(\ArrayAccess::class);

        $this->offsetExists('val1')->shouldReturn(true);
        $this->offsetExists('nope')->shouldReturn(false);

        $this->offsetGet('val1')->shouldReturn($this->items['val1']);
        $this->offsetGet('val2')->shouldReturn($this->items['val2']);

        $this->shouldThrow(\OutOfBoundsException::class)->duringOffsetGet('nope');
        $this->shouldThrow(\OutOfBoundsException::class)->duringOffsetSet('nope', true);

        $newValue = 'two';
        $this->offsetSet('val1', $newValue);
        $this->offsetGet('val1')->shouldReturn($newValue);

        $this->offsetUnset('val2');
        $this->offsetExists('val2')->shouldReturn(true);
        $this->offsetGet('val2')->shouldReturn(null);
    }
}
