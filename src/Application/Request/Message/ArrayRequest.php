<?php declare(strict_types=1);

namespace Spot\Api\Application\Request\Message;

class ArrayRequest implements RequestInterface
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
    public function getRequestName() : string
    {
        return $this->name;
    }

    public function getData() : array
    {
        return $this->data;
    }
}
