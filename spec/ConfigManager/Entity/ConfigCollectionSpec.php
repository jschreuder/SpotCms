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
}
