<?php declare(strict_types=1);

namespace Spot\Api\Response\Message;

use Spot\Api\Message\AttributesArrayAccessTrait;

class Response implements ResponseInterface
{
    use \Spot\Api\Message\AttributesArrayAccessTrait;

    /** @var  string */
    private $name;

    /** @var  array */
    private $attributes;

    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->attributes = $data;
    }

    /** {@inheritdoc} */
    public function getResponseName() : string
    {
        return $this->name;
    }
}
