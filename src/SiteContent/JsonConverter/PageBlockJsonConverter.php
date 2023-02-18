<?php declare(strict_types = 1);

namespace Spot\SiteContent\JsonConverter;

use Spot\Application\JsonOutput\JsonConverterInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;

class PageBlockJsonConverter implements JsonConverterInterface
{
    public function getType(): string
    {
        return PageBlock::TYPE;
    }

    public function getId($pageBlock): string
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSchema can only work on pageBlocks.');
        }

        return $pageBlock->getUuid()->toString();
    }

    public function getAttributes($pageBlock): array
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSchema can only work on pageBlocks.');
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

    public function getRelationships($pageBlock): array
    {
        if (!$pageBlock instanceof PageBlock) {
            throw new \InvalidArgumentException('PageBlockSchema can only work on pageBlocks.');
        }

        $page = $pageBlock->getPage();
        return [
            Page::TYPE => [
                'id' => $page->getUuid()->toString(),
                'type' => Page::TYPE,
                'attributes' => [
                    'title' => $page->getTitle(),
                    'slug' => $page->getSlug(),
                    'short_title' => $page->getShortTitle(),
                    'sort_order' => $page->getSortOrder(),
                    'status' => $page->getStatus()->toString(),
                ],
            ],
        ];
    }
}
