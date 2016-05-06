<?php declare(strict_types = 1);

namespace Spot\SiteContent\BlockType;

interface BlockTypeContainerInterface
{
    public function getType(string $typeName) : BlockTypeInterface;
}
