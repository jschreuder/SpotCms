<?php declare(strict_types=1);

namespace Spot\Api\Request\Message;

use Spot\Api\Message\AttributesArrayAccessTrait;

class Request implements RequestInterface
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
    public function getRequestName() : string
    {
        return $this->name;
    }
}
