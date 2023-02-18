<?php declare(strict_types = 1);

namespace Spot\SiteContent\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\Value\PageStatusValue;

class Page
{
    use TimestampedMetaDataTrait;

    const TYPE = 'pages';

    private string $title;
    private string $slug;
    private string $shortTitle;
    /** @var  PageBlock[] */
    private array $relatedBlocks;

    public function __construct(
        private UuidInterface $pageUuid,
        string $title,
        string $slug,
        string $shortTitle = null,
        private ?UuidInterface $parentUuid = null,
        private int $sortOrder = 0,
        private ?PageStatusValue $status = null
    )
    {
        $this->setTitle($title);
        $this->setSlug($slug);
        $this->setShortTitle($shortTitle ?: $title);
        $this->status = $status ?: PageStatusValue::get(PageStatusValue::CONCEPT);
    }

    public function getUuid(): UuidInterface
    {
        return $this->pageUuid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getShortTitle(): string
    {
        return $this->shortTitle;
    }

    public function setShortTitle(string $shortTitle): self
    {
        $this->shortTitle = $shortTitle;
        return $this;
    }

    public function getParentUuid(): ?UuidInterface
    {
        return $this->parentUuid;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getStatus(): PageStatusValue
    {
        return $this->status;
    }

    public function setStatus(PageStatusValue $status): self
    {
        $this->status = $status;
        return $this;
    }

    private function sortBlocks(): void
    {
        usort($this->relatedBlocks, function (PageBlock $a, PageBlock $b) : int {
            if ($a->getSortOrder() === $b->getSortOrder()) {
                return 0;
            }
            return $a->getSortOrder() > $b->getSortOrder() ? 1 : -1;
        });
    }

    public function hasBlocks(): bool
    {
        return isset($this->relatedBlocks);
    }

    /** @return  PageBlock[] */
    public function getBlocks(): array
    {
        if (!$this->hasBlocks()) {
            throw new \RuntimeException('Page block were not yet loaded.');
        }
        return $this->relatedBlocks;
    }

    public function setBlocks(array $blocks): self
    {
        $this->relatedBlocks = [];
        foreach ($blocks as $block) {
            $this->addBlock($block);
        }
        return $this;
    }

    public function addBlock(PageBlock $block): self
    {
        if (!$this->hasBlocks()) {
            throw new \RuntimeException('Page block were not yet loaded.');
        }
        $this->relatedBlocks[] = $block;
        $this->sortBlocks();
        return $this;
    }

    public function removeBlock(PageBlock $block): self
    {
        foreach ($this->getBlocks() as $idx => $relBlock) {
            if ($relBlock->getUuid()->equals($block->getUuid())) {
                unset($this->relatedBlocks[$idx]);
                $this->relatedBlocks = array_merge($this->relatedBlocks);
                return $this;
            }
        }
        throw new NoResultException('Block not found in Page\'s blocks: ' . $block->getUuid()->toString());
    }

    public function getBlockByUuid(UuidInterface $uuid): PageBlock
    {
        foreach ($this->getBlocks() as $block) {
            if ($block->getUuid()->equals($uuid)) {
                return $block;
            }
        }
        throw new NoResultException('Block not found in Page\'s blocks: ' . $uuid->toString());
    }
}
