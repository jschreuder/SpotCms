<?php declare(strict_types = 1);

namespace Spot\SiteContent\BlockType;

class BlockTypeContainer implements BlockTypeContainerInterface
{
    /** @var  BlockTypeInterface[] $types */
    public function __construct(private array $types)
    {
        foreach ($types as $type) {
            $this->addType($type);
        }
    }

    public function addType(BlockTypeInterface $type): self
    {
        $this->types[$type->getTypeName()] = $type;
        return $this;
    }
    
    public function getType(string $typeName): BlockTypeInterface
    {
        if (!isset($this->types[$typeName])) {
            throw new \OutOfBoundsException('No such type available: ' . $typeName);
        }
        return $this->types[$typeName];
    }
}
