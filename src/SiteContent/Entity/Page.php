<?php declare(strict_types=1);

namespace Spot\SiteContent\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\SiteContent\Value\PageStatusValue;

class Page
{
    const TYPE = 'pages';

    /** @var  UuidInterface */
    private $pageUuid;

    /** @var  string */
    private $title;

    /** @var  string */
    private $slug;

    /** @var  string */
    private $shortTitle;

    /** @var  UuidInterface */
    private $parentUuid;

    /** @var  int */
    private $sortOrder;

    /** @var  PageStatusValue */
    private $status;

    /** @var  PageBlock[] */
    private $relatedBlocks;

    public function __construct(
        UuidInterface $pageUuid,
        string $title,
        string $slug,
        string $shortTitle = null,
        UuidInterface $parentUuid = null,
        int $sortOrder = 0,
        PageStatusValue $status = null
    ) {
        $this->pageUuid = $pageUuid;
        $this->setTitle($title);
        $this->setSlug($slug);
        $this->setShortTitle($shortTitle ?: $title);
        $this->parentUuid = $parentUuid;
        $this->sortOrder = $sortOrder;
        $this->status = $status ?: PageStatusValue::get(PageStatusValue::CONCEPT);
    }

    public function getUuid() : UuidInterface
    {
        return $this->pageUuid;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug() : string
    {
        return $this->slug;
    }

    public function setSlug(string $slug) : self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getShortTitle() : string
    {
        return $this->shortTitle;
    }

    public function setShortTitle(string $shortTitle) : self
    {
        $this->shortTitle = $shortTitle;
        return $this;
    }

    /** @return  UuidInterface|null */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    public function getSortOrder() : int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder) : self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getStatus() : PageStatusValue
    {
        return $this->status;
    }

    public function setStatus(PageStatusValue $status)
    {
        $this->status = $status;
        return $this;
    }

    public function setBlocks(array $blocks) : self
    {
        $this->relatedBlocks = [];
        foreach ($blocks as $block) {
            $this->addBlock($block);
        }
        usort($this->relatedBlocks, function (PageBlock $a, PageBlock $b) : int {
            if ($a->getSortOrder() === $b->getSortOrder()) {
                return 0;
            }
            return $a->getSortOrder() > $b->getSortOrder() ? 1 : -1;
        });
        return $this;
    }

    public function addBlock(PageBlock $block) : self
    {
        $this->relatedBlocks[] = $block;
        return $this;
    }

    public function getBlocks() : array
    {
        if (is_null($this->relatedBlocks)) {
            throw new \RuntimeException('Page block were not yet loaded.');
        }
        return $this->relatedBlocks;
    }
}
