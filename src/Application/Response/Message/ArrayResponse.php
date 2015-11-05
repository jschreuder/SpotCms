<?php declare(strict_types=1);

namespace Spot\Cms\Application\Response\Message;

class ArrayResponse implements ResponseInterface
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
}
