<?php declare(strict_types=1);

namespace Spot\Api\Request\Message;

use Spot\Api\Message\AttributesArrayAccessTrait;

class NotFoundRequest implements RequestInterface
{
    use \Spot\Api\Message\AttributesArrayAccessTrait;

    /** @var  array */
    private $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'error.notFound';
    }
}
