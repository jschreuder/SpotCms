<?php declare(strict_types = 1);

namespace Spot\SiteContent\BlockType;

use jschreuder\Middle\Exception\ValidationFailedException;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

class RssFeedBlockType implements BlockTypeInterface
{
    const TYPE = 'rssFeed';

    public function getTypeName(): string
    {
        return self::TYPE;
    }

    public function newBlock(Page $page, string $location, int $sortOrder, PageStatusValue $status): PageBlock
    {
        return new PageBlock(
            Uuid::uuid4(),
            $page,
            $this->getTypeName(),
            ['feedUrl' => null],
            $location,
            $sortOrder,
            $status
        );
    }

    public function validate(PageBlock $block): void
    {
        $params = $block->getParameters();

        if (
            !array_key_exists('feedUrl', $params)
            || preg_match('#^https?://#uiD', $params['feedUrl']) < 1
        ) {
            throw new ValidationFailedException([]);
        }
    }
}
