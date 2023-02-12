<?php declare(strict_types = 1);

namespace Spot\SiteContent\Serializer;

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Schema\BaseSchema;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;

class PageBlockSerializer extends BaseSchema
{
    public function getType(): string
    {
        return PageBlock::TYPE;
    }

    public function getId($pageBlock): string
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSerializer can only serialize pageBlocks.');
        }

        return $pageBlock->getUuid()->toString();
    }

    public function getAttributes($pageBlock, ContextInterface $context): iterable
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

    public function getRelationships($pageBlock, ContextInterface $context): iterable
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSerializer can only serialize pageBlocks.');
        }

        return [
            Page::TYPE => [
                self::RELATIONSHIP_LINKS_SELF    => true,
                self::RELATIONSHIP_LINKS_RELATED => true,
                self::RELATIONSHIP_DATA => $pageBlock->getPage(),
            ],
        ];
    }
}
