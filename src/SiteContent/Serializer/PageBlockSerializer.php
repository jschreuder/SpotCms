<?php declare(strict_types=1);

namespace Spot\SiteContent\Serializer;

use Spot\SiteContent\Entity\PageBlock;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\SerializerInterface;

class PageBlockSerializer implements SerializerInterface
{
    public function getType($model) : string
    {
        return PageBlock::TYPE;
    }

    public function getId($pageBlock) : string
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSerializer can only serialize pageBlocks.');
        }

        return $pageBlock->getUuid()->toString();
    }

    public function getAttributes($pageBlock, array $fields = null) : array
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSerializer can only serialize pageBlocks.');
        }

        return [
            'type' => $pageBlock->getType(),
            'parameters' => $pageBlock->getParameters(),
            'location' => $pageBlock->getLocation(),
            'sort_order' => $pageBlock->getSortOrder(),
            'status' => $pageBlock->getStatus()->toString(),
        ];
    }

    public function getRelationship($pageBlock, $name) : Relationship
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSerializer can only serialize pageBlocks.');
        }

        throw new \OutOfBoundsException('Unknown relationship ' . $name . ' for ' . $this->getType($pageBlock));
    }
}
