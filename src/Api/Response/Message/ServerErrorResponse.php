<?php declare(strict_types=1);

namespace Spot\Api\Response\Message;

use Spot\Api\Message\AttributesArrayAccessTrait;

class ServerErrorResponse implements ResponseInterface
{
    use \Spot\Api\Message\AttributesArrayAccessTrait;

    /** @var  array */
    private $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /** {@inheritdoc} */
    public function getResponseName() : string
    {
        return 'error.serverError';
    }
}
