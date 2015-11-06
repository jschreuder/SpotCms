<?php declare(strict_types=1);

namespace Spot\Api\Application\Request\Message;

class BadRequest implements RequestInterface
{
    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'error.badRequest';
    }
}
