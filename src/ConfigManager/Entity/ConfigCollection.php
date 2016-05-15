<?php declare(strict_types = 1);

namespace Spot\ConfigManager\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\ConfigManager\ConfigType\ConfigTypeInterface;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;

class ConfigCollection implements \ArrayAccess
{
    use TimestampedMetaDataTrait;

    const TYPE = 'configCollections';

    /** @var  UuidInterface */
    private $configCollectionUuid;

    /** @var  ConfigTypeInterface */
    private $type;

    /** @var  string */
    private $name;

    /** @var  array */
    private $items;

    public function __construct(UuidInterface $uuid, ConfigTypeInterface $type, string $name)
    {
        $this->configCollectionUuid = $uuid;
        $this->type = $type;
        $this->name = $name;
        $this->items = $type->getDefaultItems();
    }

    public function getUuid() : UuidInterface
    {
        return $this->configCollectionUuid;
    }

    public function getType() : ConfigTypeInterface
    {
        return $this->type;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setItem(string $name, $value) : self
    {
        if (!$this->hasItem($name)) {
            throw new \OutOfBoundsException('ConfigCollection does not have an item named: ' . $name);
        }
        $this->items[$name] = $value;
        return $this;
    }

    public function hasItem(string $name) : bool
    {
        return array_key_exists($name, $this->items);
    }

    public function getItem(string $name)
    {
        if (!$this->hasItem($name)) {
            throw new \OutOfBoundsException('ConfigCollection does not have an item named: ' . $name);
        }
        return $this->items[$name];
    }

    public function getItems() : array
    {
        return $this->items;
    }

    public function offsetExists($offset)
    {
        return $this->hasItem($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getItem($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setItem($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->setItem($offset, null);
    }
}
