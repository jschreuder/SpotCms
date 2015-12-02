<?php declare(strict_types=1);

namespace Spot\SiteContent\Serializer;

use Spot\SiteContent\Entity\Page;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\SerializerInterface;

class PageSerializer implements SerializerInterface
{
    public function getType($model) : string
    {
        return 'pages';
    }

    public function getId($page) : string
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSerializer can only serialize pages.');
        }

        return $page->getUuid()->toString();
    }

    public function getAttributes($page, array $fields = null) : array
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
        ];
    }

    public function getRelationship($page, $name) : Relationship
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('PageSerializer can only serialize pages.');
        }

        throw new \OutOfBoundsException('Unknown relationship ' . $name . ' for ' . $this->getType($page));
    }
}
