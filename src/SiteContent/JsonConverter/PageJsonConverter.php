<?php declare(strict_types = 1);

namespace Spot\SiteContent\JsonConverter;

use Spot\Application\JsonConverter\JsonConverterInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;

class PageJsonConverter implements JsonConverterInterface
{
    public function getType(): string
    {
        return Page::TYPE;
    }

    public function getId($page): string
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSchema can only work on pages.');
        }

        return $page->getUuid()->toString();
    }

    public function getAttributes($page): array
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSchema can only work on pages.');
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

    public function getRelationships($page): array
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSchema can only work on pages.');
        }

        if (!$page->hasBlocks()) {
            return [];
        }

        $pageBlocks = [];
        foreach ($page->getBlocks() as $block) {
            $pageBlocks[] = [
                'page_block_id' => $block->getUuid()->toString(),
                'type' => PageBlock::TYPE,
                'attributes' => [
                    'type' => $block->getType(),
                    'parameters' => $block->getParameters(),
                    'location' => $block->getLocation(),
                    'sort_order' => $block->getSortOrder(),
                    'status' => $block->getStatus()->toString(),
                ],
            ];
        }
        return [
            PageBlock::TYPE => $pageBlocks,
        ];
    }
}
