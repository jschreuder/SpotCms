<?php declare(strict_types = 1);

namespace Spot\SiteContent\Serializer;

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Schema\BaseSchema;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;

class PageSerializer extends BaseSchema
{
    public function getType(): string
    {
        return Page::TYPE;
    }

    public function getId($page): string
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSerializer can only serialize pages.');
        }

        return $page->getUuid()->toString();
    }

    public function getAttributes($page, ContextInterface $context): iterable
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSerializer can only serialize pages.');
        }

        return [
            'title' => $page->getTitle(),
            'slug' => $page->getSlug(),
            'short_title' => $page->getShortTitle(),
            'parent_uuid' => $page->getParentUuid() ? $page->getParentUuid()->toString() : null,
            'sort_order' => $page->getSortOrder(),
            'status' => $page->getStatus()->toString(),
            'meta' => [
                'created' => $page->metaDataGetCreatedTimestamp()->format('c'),
                'updated' => $page->metaDataGetCreatedTimestamp()->format('c'),
            ],
        ];
    }

    public function getRelationships($page, ContextInterface $context): iterable
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSerializer can only serialize pages.');
        }

        return [
            PageBlock::TYPE => [
                self::RELATIONSHIP_LINKS_SELF    => true,
                self::RELATIONSHIP_LINKS_RELATED => true,
                self::RELATIONSHIP_DATA => $page->getBlocks(),
            ],
        ];
    }
}
