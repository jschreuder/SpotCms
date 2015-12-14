<?php declare(strict_types = 1);

namespace Spot\SiteContent\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Entity\ObjectMetaDataTrait;
use Spot\SiteContent\Value\PageStatusValue;

class PageBlock implements \ArrayAccess
{
    use ObjectMetaDataTrait;

    const TYPE = 'pageBlocks';

    /** @var  UuidInterface */
    private $pageBlockUuid;

    /** @var  Page */
    private $page;

    /** @var  string */
    private $type;

    /** @var  array */
    private $parameters;

    /** @var  string */
    private $location;

    /** @var  int */
    private $sortOrder;

    /** @var  PageStatusValue */
    private $status;

    public function __construct(
        UuidInterface $pageBlockUuid,
        Page $page,
        string $type,
        array $parameters,
        string $location,
        int $sortOrder,
        PageStatusValue $status = null
    ) {
        $this->pageBlockUuid = $pageBlockUuid;
        $this->page = $page;
        $this->type = $type;
        $this->parameters = $parameters;
        $this->location = $location;
        $this->sortOrder = $sortOrder;
        $this->status = $status ?: PageStatusValue::get(PageStatusValue::CONCEPT);
    }

    public function getUuid() : UuidInterface
    {
        return $this->pageBlockUuid;
    }

    public function getPage() : Page
    {
        return $this->page;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getLocation() : string
    {
        return $this->location;
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

    public function setStatus(PageStatusValue $status) : self
    {
        $this->status = $status;
        return $this;
    }

    public function offsetExists($offset) : bool
    {
        return array_key_exists($offset, $this->parameters);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException('No such parameter set: ' . $offset);
        }
        return $this->parameters[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->parameters[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->parameters[$offset]);
    }
}
