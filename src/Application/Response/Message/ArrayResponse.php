<?php declare(strict_types=1);

namespace Spot\Api\Application\Response\Message;

class ArrayResponse implements ResponseInterface, \ArrayAccess
{
    /** @var  string */
    private $name;

    /** @var  array */
    private $data;

    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    /** {@inheritdoc} */
    public function getResponseName() : string
    {
        return $this->name;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function offsetExists($offset) : bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        if (!isset($this[$offset])) {
            throw new \OutOfBoundsException('No such offset: ' . $offset);
        }
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
