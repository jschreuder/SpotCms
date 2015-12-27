<?php declare(strict_types = 1);

namespace Spot\SiteContent\Serializer;

use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\Resource;
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
            'meta' => [
                'created' => $pageBlock->metaDataGetCreatedTimestamp()->format('c'),
                'updated' => $pageBlock->metaDataGetCreatedTimestamp()->format('c'),
            ],
        ];
    }

    public function getRelationship($pageBlock, $name) : Relationship
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSerializer can only serialize pageBlocks.');
        }

        if ($name === Page::TYPE) {
            return new Relationship(new Resource($pageBlock->getPage(), new PageSerializer()));
        }

        throw new \OutOfBoundsException('Unknown relationship ' . $name . ' for ' . $this->getType($pageBlock));
    }
}
