<?php declare(strict_types = 1);

namespace Spot\SiteContent\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;
use Spot\SiteContent\Value\PageStatusValue;

class PageBlock implements \ArrayAccess
{
    use TimestampedMetaDataTrait;

    const TYPE = 'pageBlocks';

    public function __construct(
        private UuidInterface $pageBlockUuid,
        private Page $page,
        private string $type,
        private array $parameters,
        private string $location,
        private int $sortOrder,
        private ?PageStatusValue $status = null
    )
    {
        $this->status = $status ?: PageStatusValue::get(PageStatusValue::CONCEPT);
    }

    public function getUuid(): UuidInterface
    {
        return $this->pageBlockUuid;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): PageBlock
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getStatus(): PageStatusValue
    {
        return $this->status;
    }

    public function setStatus(PageStatusValue $status): PageBlock
    {
        $this->status = $status;
        return $this;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->parameters);
    }

    public function offsetGet($offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException('No such parameter set: ' . $offset);
        }
        return $this->parameters[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->parameters[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->parameters[$offset]);
    }
}
