<?php declare(strict_types = 1);

namespace Spot\SiteContent\BlockType;

use Spot\Api\Request\RequestInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

interface BlockTypeInterface
{
    /**
     * Returns the name for this BlockType to be used in storage
     */
    public function getTypeName() : string;

    /**
     * Factory method for creating a new PageBlock with defaults for this type
     */
    public function newBlock(Page $page, string $location, int $sortOrder, PageStatusValue $status) : PageBlock;

    /**
     * Validates if the given PageBlock is correctly configured
     *
     * @return  void
     * @throws  \Spot\Application\Response\ValidationFailedException
     */
    public function validate(PageBlock $block, RequestInterface $request);
}
