<?php declare(strict_types=1);

namespace Spot\Api\Content\Serializer;

use Spot\Api\Content\Entity\Page;
use Tobscure\JsonApi\AbstractSerializer;

class PageSerializer extends AbstractSerializer
{
    protected $type = 'pages';

    public function getAttributes($page, array $fields = [])
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSerializer can only serialize pages.');
        }

        return [
            'page_uuid' => $page->getUuid()->toString(),
            'title' => $page->getTitle(),
            'slug' => $page->getSlug(),
            'short_title' => $page->getShortTitle(),
            'parent_uuid' => $page->getParentUuid() ? $page->getParentUuid()->toString() : null,
            'sort_order' => $page->getSortOrder(),
            'status' => $page->getStatus()->toString(),
        ];
    }
}
